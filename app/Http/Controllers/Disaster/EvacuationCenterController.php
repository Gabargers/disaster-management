<?php

namespace App\Http\Controllers\Disaster;

use App\Enums\FamilyStatus;
use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterAssignment;
use App\Models\Disaster\EvacuationCenterPayoutSession;
use App\Models\Disaster\PayoutRelease;
use App\Models\Disaster\PayoutSchedule;
use App\Models\Disaster\PostPayoutRequirement;
use App\Services\Disaster\DisasterAssistanceWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EvacuationCenterController extends Controller
{
    public function __construct(private DisasterAssistanceWorkflowService $workflow) {}
    public function index()
    {
        return view('disaster.payouts', [
            'page_title' => 'Evacuation Center', 'page_description' => 'Manage evacuation centers, beneficiaries, and payout releases.',
            'centers' => EvacuationCenter::with(['barangay', 'disaster', 'payoutSessions' => fn ($q) => $q->latest('payout_date')])->withCount('activeAssignments')->orderBy('name')->get(),
            'barangays' => Barangay::where('is_active', true)->orderBy('name')->get(), 'disasters' => Disaster::orderByDesc('incident_date')->get(),
            'officers' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCenter($request);
        $center = EvacuationCenter::create($data + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id, 'is_active' => $data['status'] === 'ACTIVE']);
        return response()->json(['success' => true, 'message' => 'Evacuation center created.', 'data' => $center], 201);
    }

    public function update(Request $request, EvacuationCenter $center): JsonResponse
    {
        $data = $this->validateCenter($request, $center);
        $center->update($data + ['updated_by' => $request->user()->id, 'is_active' => $data['status'] === 'ACTIVE']);
        return response()->json(['success' => true, 'message' => 'Evacuation center updated.', 'data' => $center]);
    }

    private function validateCenter(Request $request, ?EvacuationCenter $center = null): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'disaster_id' => ['required','exists:disasters,id'], 'barangay_id' => ['required','exists:barangays,id'],
            'address' => ['required','string','max:1000'], 'contact_person' => ['nullable','string','max:255'],
            'contact_number' => ['nullable','regex:/^(09|\+639)\d{9}$/'], 'capacity' => ['required','integer','min:1'],
            'description' => ['nullable','string','max:2000'], 'status' => ['required', Rule::in(['ACTIVE','INACTIVE','FULL','CLOSED'])],
            'payout_availability' => ['required', Rule::in(['AVAILABLE','NOT_AVAILABLE','COMPLETED'])],
            'default_payout_date' => ['nullable','date'], 'default_payout_start_time' => ['nullable','date_format:H:i'], 'default_payout_end_time' => ['nullable','date_format:H:i','after:default_payout_start_time'],
        ]);
        if ($data['status'] !== 'ACTIVE' && $data['payout_availability'] === 'AVAILABLE') throw ValidationException::withMessages(['payout_availability' => 'Only active centers can be available for payout.']);
        $duplicate = EvacuationCenter::where('name', $data['name'])->where('address', $data['address'])->when($center, fn ($q) => $q->whereKeyNot($center->id))->exists();
        if ($duplicate) throw ValidationException::withMessages(['name' => 'A center with the same name and address already exists.']);
        return $data;
    }

    public function show(EvacuationCenter $center)
    {
        $center->load(['barangay', 'disaster', 'payoutSessions' => fn ($q) => $q->latest('payout_date')]);
        $assignments = $center->activeAssignments()->with('family.familyMembers')->get();
        $additionalMembers = $assignments->sum(fn ($assignment) => $assignment->family->familyMembers->count());
        $assigned = $assignments->count();
        return view('disaster.evacuation-center-show', [
            'page_title' => $center->name, 'page_description' => 'Assigned evacuees and beneficiary payout processing.',
            'center' => $center, 'session' => $center->payoutSessions->first(),
            'summary' => ['families' => $assigned, 'evacuees' => $assigned + $additionalMembers, 'available' => max(0, (int) $center->capacity - ($assigned + $additionalMembers))],
            'disasters' => Disaster::orderByDesc('incident_date')->get(['id', 'name']),
        ]);
    }

    public function families(Request $request, EvacuationCenter $center): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable','string','max:255'], 'per_page' => ['nullable','integer','min:5','max:100'],
            'sort' => ['nullable', Rule::in(['assigned_at','household','dafac','status'])], 'direction' => ['nullable', Rule::in(['asc','desc'])],
            'workflow_status' => ['nullable','string','max:50'], 'payout_status' => ['nullable','string','max:30'],
            'housing_condition' => ['nullable','string','max:50'], 'house_ownership' => ['nullable','string','max:30'],
            'disaster_id' => ['nullable','integer','exists:disasters,id'], 'assigned_from' => ['nullable','date'], 'assigned_to' => ['nullable','date','after_or_equal:assigned_from'],
        ]);
        $query = $center->activeAssignments()->with(['family.barangay','family.disaster','family.dafacRecord','family.tcissMasterlistRecord','family.familyMembers'])
            ->when($request->filled('search'), function ($q) use ($request) { $search='%'.$request->string('search').'%'; $q->whereHas('family', fn($f)=>$f->where('household_head_surname','like',$search)->orWhere('household_head_given_name','like',$search)->orWhere('complete_address','like',$search)->orWhereHas('dafacRecord',fn($d)=>$d->where('reference_number','like',$search))->orWhereHas('tcissMasterlistRecord',fn($t)=>$t->where('source_reference','like',$search))->orWhereHas('familyMembers',fn($m)=>$m->where('name','like',$search))); })
            ->when($request->filled('workflow_status'), fn($q)=>$q->whereHas('family',fn($f)=>$f->where('status',$request->workflow_status)))
            ->when($request->filled('housing_condition'), fn($q)=>$q->whereHas('family',fn($f)=>$f->where('housing_condition',$request->housing_condition)))
            ->when($request->filled('house_ownership'), fn($q)=>$q->whereHas('family',fn($f)=>$f->where('house_ownership',$request->house_ownership)))
            ->when($request->filled('disaster_id'), fn($q)=>$q->where('disaster_id',$request->integer('disaster_id')))
            ->when($request->filled('assigned_from'), fn($q)=>$q->whereDate('assigned_at','>=',$request->assigned_from))
            ->when($request->filled('assigned_to'), fn($q)=>$q->whereDate('assigned_at','<=',$request->assigned_to));
        if ($request->filled('payout_status')) $query->whereHas('family.payoutReleases',fn($q)=>$q->where('evacuation_center_id',$center->id)->where('status',$request->payout_status));
        $direction=$data['direction']??'desc'; $sort=$data['sort']??'assigned_at';
        if($sort==='assigned_at') $query->orderBy('assigned_at',$direction);
        elseif($sort==='household') $query->orderBy(AffectedFamily::select('household_head_surname')->whereColumn('affected_families.id','evacuation_center_assignments.affected_family_id')->limit(1),$direction);
        elseif($sort==='status') $query->orderBy(AffectedFamily::select('status')->whereColumn('affected_families.id','evacuation_center_assignments.affected_family_id')->limit(1),$direction);
        else $query->orderBy(DafacRecord::select('reference_number')->whereColumn('dafac_records.affected_family_id','evacuation_center_assignments.affected_family_id')->limit(1),$direction);
        $page=$query->paginate($data['per_page']??15);
        $rows=$page->getCollection()->map(function($assignment)use($center){$f=$assignment->family;$p=$f->payoutReleases()->where('evacuation_center_id',$center->id)->latest()->first();return ['assignment_id'=>$assignment->id,'family_id'=>$f->id,'dafac_reference'=>$f->dafacRecord?->reference_number,'tciss_reference'=>$f->tcissMasterlistRecord?->source_reference,'household_head'=>$f->household_head_full_name,'address'=>$f->complete_address,'barangay'=>$f->barangay?->name,'family_members'=>$f->familyMembers->count(),'household_size'=>$f->familyMembers->count()+1,'housing_condition'=>$f->housing_condition,'house_ownership'=>$f->house_ownership,'workflow_status'=>$f->status?->value??$f->status,'payout_status'=>$p?->status??'Not Scheduled','photo_status'=>$p?->payout_photo_path?'Captured':'Missing','assigned_at'=>$assignment->assigned_at?->toIso8601String(),'open_url'=>route('disaster.payouts.centers.families.payout-details',[$center,$f])];});
        return response()->json(['success'=>true,'data'=>$rows,'meta'=>['current_page'=>$page->currentPage(),'last_page'=>$page->lastPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'from'=>$page->firstItem(),'to'=>$page->lastItem()]]);
    }

    public function payoutDetails(EvacuationCenter $center, AffectedFamily $family): JsonResponse
    {
        abort_unless($center->activeAssignments()->where('affected_family_id',$family->id)->exists(),404,'This family is not currently assigned to the evacuation center.');
        $family->load(['barangay','dafacRecord','tcissMasterlistRecord','familyMembers','payoutReleases'=>fn($q)=>$q->where('evacuation_center_id',$center->id)->latest(),'payoutReleases.releaser']);
        $session=$center->payoutSessions()->latest('payout_date')->first(); $payout=$family->payoutReleases->first();
        return response()->json(['success'=>true,'data'=>[
            'affected_family'=>['id'=>$family->id,'household_head'=>$family->household_head_full_name,'birthdate'=>$family->birthdate?->format('Y-m-d'),'age'=>$family->age,'address'=>$family->complete_address,'barangay'=>$family->barangay?->name,'family_members'=>$family->familyMembers->count(),'household_size'=>$family->familyMembers->count()+1,'housing_condition'=>$family->housing_condition,'house_ownership'=>$family->house_ownership,'workflow_status'=>$family->status?->value??$family->status],
            'dafac'=>['reference'=>$family->dafacRecord?->reference_number],'tciss'=>['reference'=>$family->tcissMasterlistRecord?->source_reference],'evacuation_center'=>['id'=>$center->id,'name'=>$center->name],
            'family_members'=>$family->familyMembers->map(fn($m)=>['name'=>$m->name,'birthdate'=>$m->birthdate?->format('Y-m-d'),'age'=>$m->age,'relationship'=>$m->relationship_to_head,'sex'=>$m->sex,'occupation'=>$m->occupation,'health_condition'=>$m->health_condition,'remarks_code'=>$m->remarks_codes]),
            'payout'=>$payout?['id'=>$payout->id,'status'=>$payout->status,'assistance_kind'=>$payout->assistance_kind,'quantity'=>$payout->quantity,'amount'=>$payout->amount,'provider'=>$payout->provider,'notes'=>$payout->photo_caption,'payout_date'=>$session?->payout_date?->format('Y-m-d'),'released_at'=>$payout->released_at?->toIso8601String(),'released_by'=>$payout->releaser?->name,'has_photo'=>(bool)$payout->payout_photo_path,'photo_url'=>$payout->payout_photo_path?route('disaster.payouts.releases.photo',$payout):null,'can_release'=>$payout->status==='Scheduled'&&$family->status===FamilyStatus::PAYOUT_SCHEDULED]:null,
            'defaults'=>['assistance_kind'=>$session?->assistance_type,'amount'=>$session?->default_amount,'provider'=>$session?->provider,'payout_date'=>$session?->payout_date?->format('Y-m-d')],
            'payout_history'=>$family->payoutReleases->map(fn($p)=>['status'=>$p->status,'assistance_kind'=>$p->assistance_kind,'amount'=>$p->amount,'provider'=>$p->provider,'released_at'=>$p->released_at?->toIso8601String(),'released_by'=>$p->releaser?->name]),
        ]]);
    }

    public function photo(PayoutRelease $release)
    {
        abort_unless($release->payout_photo_path && Storage::disk('local')->exists($release->payout_photo_path), 404);
        return Storage::disk('local')->response($release->payout_photo_path, 'beneficiary-payout-photo', ['Cache-Control' => 'private, max-age=300']);
    }

    public function availableFamilies(Request $request, EvacuationCenter $center): JsonResponse
    {
        $families = AffectedFamily::with(['barangay','familyMembers','tcissMasterlistRecord','dafacRecord','evacuationCenter'])
            ->where('disaster_id',$center->disaster_id)->whereIn('status',[FamilyStatus::SUBMITTED_FOR_PAYROLL,FamilyStatus::PAYOUT_PENDING,FamilyStatus::PAYOUT_SCHEDULED,FamilyStatus::ASSISTANCE_RELEASED,FamilyStatus::REQUIREMENTS_PENDING,FamilyStatus::REQUIREMENTS_COMPLETED])->when($request->filled('search'), function($q) use($request){$s='%'.$request->string('search').'%';$q->where(fn($q)=>$q->where('household_head_surname','like',$s)->orWhere('household_head_given_name','like',$s)->orWhere('complete_address','like',$s)->orWhereHas('tcissMasterlistRecord',fn($q)=>$q->where('source_reference','like',$s)));})->limit(100)->get();
        return response()->json(['success'=>true,'data'=>$families->map(fn($f)=>['id'=>$f->id,'tciss'=>$f->tcissMasterlistRecord?->source_reference,'dafac'=>$f->dafacRecord?'DAFAC-'.str_pad((string)$f->dafacRecord->id,4,'0',STR_PAD_LEFT):null,'head'=>$f->household_head_full_name,'barangay'=>$f->barangay?->name,'address'=>$f->complete_address,'members'=>$f->familyMembers->count(),'housing'=>$f->housing_condition,'status'=>$f->status?->value ?? $f->status,'current_center'=>$f->evacuationCenter?->name])]);
    }

    public function assign(Request $request, EvacuationCenter $center): JsonResponse
    {
        $data=$request->validate(['family_ids'=>['required','array','min:1'],'family_ids.*'=>['integer','exists:affected_families,id'],'confirm_reassignment'=>['boolean']]);
        return DB::transaction(function() use($data,$request,$center){
            $active=$center->activeAssignments()->count(); if($active+count($data['family_ids'])>$center->capacity) throw ValidationException::withMessages(['family_ids'=>'Assignment exceeds the center capacity.']);
            foreach(array_unique($data['family_ids']) as $id){ $family=AffectedFamily::lockForUpdate()->findOrFail($id); abort_unless(in_array($family->status,[FamilyStatus::SUBMITTED_FOR_PAYROLL,FamilyStatus::PAYOUT_PENDING],true),422,'Only submitted payroll families can be assigned.'); abort_unless($family->disaster_id===$center->disaster_id,422,'Family belongs to another disaster.'); $previous=EvacuationCenterAssignment::where('affected_family_id',$id)->where('disaster_id',$center->disaster_id)->where('status','ACTIVE')->lockForUpdate()->first(); if($previous && $previous->evacuation_center_id!==$center->id && empty($data['confirm_reassignment'])) throw ValidationException::withMessages(['family_ids'=>'A selected family is already assigned. Confirm reassignment to continue.']); if($previous && $previous->evacuation_center_id!==$center->id)$previous->update(['status'=>'REASSIGNED','unassigned_at'=>now()]); EvacuationCenterAssignment::firstOrCreate(['evacuation_center_id'=>$center->id,'affected_family_id'=>$id,'disaster_id'=>$center->disaster_id,'status'=>'ACTIVE'],['assigned_by'=>$request->user()->id,'assigned_at'=>now()]); $family->update(['evacuation_center_id'=>$center->id]); if($family->status===FamilyStatus::SUBMITTED_FOR_PAYROLL)$this->workflow->transition($family->refresh(),FamilyStatus::PAYOUT_PENDING,$request->user(),'evacuation_center_assigned',null,['evacuation_center_id'=>$center->id]); }
            return response()->json(['success'=>true,'message'=>'Families assigned successfully.']);
        });
    }

    public function availability(Request $request, EvacuationCenter $center): JsonResponse
    {
        $data=$request->validate(['payout_availability'=>['required',Rule::in(['AVAILABLE','NOT_AVAILABLE','COMPLETED'])],'payout_date'=>['required_if:payout_availability,AVAILABLE','nullable','date','after_or_equal:today'],'start_time'=>['required_if:payout_availability,AVAILABLE','nullable','date_format:H:i'],'end_time'=>['required_if:payout_availability,AVAILABLE','nullable','date_format:H:i','after:start_time'],'payout_area'=>['required_if:payout_availability,AVAILABLE','nullable','string'],'assigned_officer_id'=>['nullable','exists:users,id'],'assistance_type'=>['required_if:payout_availability,AVAILABLE','nullable','string'],'default_amount'=>['nullable','numeric','min:0'],'provider'=>['required_if:payout_availability,AVAILABLE','nullable','string'],'notes'=>['nullable','string']]);
        if($data['payout_availability']==='AVAILABLE' && ($center->status!=='ACTIVE'||$center->activeAssignments()->doesntExist())) throw ValidationException::withMessages(['payout_availability'=>'An active center with assigned families is required.']);
        return DB::transaction(function() use($data,$request,$center){$old=$center->payout_availability;$center->update(['payout_availability'=>$data['payout_availability']]);$session=null;if($data['payout_availability']==='AVAILABLE'){$session=EvacuationCenterPayoutSession::create($data+['evacuation_center_id'=>$center->id,'disaster_id'=>$center->disaster_id,'status'=>'OPEN','created_by'=>$request->user()->id]);$legacy=PayoutSchedule::create(['disaster_id'=>$center->disaster_id,'title'=>$center->name.' Payout','scheduled_date'=>$data['payout_date'],'venue'=>$data['payout_area'],'notes'=>$data['notes']??null,'created_by'=>$request->user()->id]);foreach($center->activeAssignments()->with('family')->get() as $assignment){$family=$assignment->family;if($family->status!==FamilyStatus::PAYOUT_PENDING)continue;PayoutRelease::firstOrCreate(['payout_session_id'=>$session->id,'affected_family_id'=>$family->id],['payout_schedule_id'=>$legacy->id,'evacuation_center_id'=>$center->id,'status'=>'Scheduled','assistance_kind'=>$data['assistance_type'],'amount'=>$data['default_amount']??null,'provider'=>$data['provider']]);$this->workflow->transition($family,FamilyStatus::PAYOUT_SCHEDULED,$request->user(),'payout_scheduled',null,['payout_session_id'=>$session->id]);}}AuditLog::create(['user_id'=>$request->user()->id,'auditable_type'=>$center::class,'auditable_id'=>$center->id,'action'=>'payout_availability_changed','old_values'=>['payout_availability'=>$old],'new_values'=>['payout_availability'=>$center->payout_availability],'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent()]);return response()->json(['success'=>true,'message'=>'Payout availability updated.','data'=>$session]);});
    }

    public function release(Request $request, PayoutRelease $release): JsonResponse
    {
        $data=$request->validate(['photo'=>['nullable','image','mimes:jpeg,jpg,png,webp','max:8192'],'assistance_kind'=>['required','string'],'quantity'=>['nullable','numeric','min:0'],'amount'=>['required','numeric','min:0'],'provider'=>['required','string'],'photo_caption'=>['nullable','string','max:255'],'confirmed'=>['accepted'],'idempotency_key'=>['required','uuid']]);
        return DB::transaction(function() use($data,$request,$release){$release=PayoutRelease::lockForUpdate()->findOrFail($release->id);if($release->status==='Released')return response()->json(['success'=>false,'message'=>'This payout has already been released.'],409);if($release->status!=='Scheduled'||$release->affectedFamily->status!==FamilyStatus::PAYOUT_SCHEDULED)return response()->json(['success'=>false,'message'=>'Only scheduled payouts can be released.'],422);if($release->center?->payout_availability!=='AVAILABLE')return response()->json(['success'=>false,'message'=>'The evacuation center is not available for payout.'],422);if(!$release->center?->activeAssignments()->where('affected_family_id',$release->affected_family_id)->exists())return response()->json(['success'=>false,'message'=>'The family is not assigned to this center.'],422);if(!$request->hasFile('photo')&&!$release->payout_photo_path)return response()->json(['success'=>false,'message'=>'A beneficiary payout photo is required.'],422);$path=$request->hasFile('photo')?$request->file('photo')->store('payout-photos','local'):$release->payout_photo_path;$release->update(['status'=>'Released','payout_photo_path'=>$path,'release_photo_path'=>$path,'assistance_kind'=>$data['assistance_kind'],'quantity'=>$data['quantity']??1,'amount'=>$data['amount'],'provider'=>$data['provider'],'photo_caption'=>$data['photo_caption']??null,'photo_taken_at'=>now(),'photo_uploaded_by'=>$request->user()->id,'released_by'=>$request->user()->id,'released_at'=>now(),'idempotency_key'=>$data['idempotency_key']]);$family=$this->workflow->transition($release->affectedFamily,FamilyStatus::ASSISTANCE_RELEASED,$request->user(),'payout_released',null,['payout_release_id'=>$release->id]);PostPayoutRequirement::firstOrCreate(['affected_family_id'=>$family->id]);$this->workflow->transition($family->refresh(),FamilyStatus::REQUIREMENTS_PENDING,$request->user(),'requirements_activated');AuditLog::create(['user_id'=>$request->user()->id,'auditable_type'=>$release::class,'auditable_id'=>$release->id,'action'=>'payout_released','new_values'=>['amount'=>$release->amount,'released_at'=>$release->released_at],'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent()]);return response()->json(['success'=>true,'message'=>'Payout released successfully.','data'=>['reference'=>'PAYOUT-'.str_pad((string)$release->id,6,'0',STR_PAD_LEFT),'household_head'=>$family->household_head_full_name,'amount'=>$release->amount,'released_at'=>$release->released_at->toIso8601String(),'released_by'=>$request->user()->name]]);});
    }
}
