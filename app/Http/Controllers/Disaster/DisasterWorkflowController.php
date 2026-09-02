<?php
namespace App\Http\Controllers\Disaster;
use App\Exports\EvacueeMonitoringReportExport;
use App\Models\Disaster\EvacuationCenterAssignment;
use App\Enums\FamilyStatus; use App\Http\Controllers\Controller; use App\Models\Cms\Barangay; use App\Models\Disaster\AffectedFamily; use App\Models\Disaster\Disaster; use App\Models\Disaster\DuplicateCheck; use App\Models\Disaster\EvacuationCenter; use App\Models\Disaster\PayrollBatch; use App\Models\Disaster\PayrollRecord; use App\Models\Disaster\PayoutRelease; use App\Models\Disaster\PostPayoutRequirement; use App\Models\Disaster\ValidationRecord; use App\Models\Integration\PersonAffected; use App\Services\Disaster\DisasterAssistanceWorkflowService; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Storage; use Illuminate\Support\Str; use Illuminate\Validation\Rule; use Maatwebsite\Excel\Facades\Excel;
class DisasterWorkflowController extends Controller {
 public function __construct(private DisasterAssistanceWorkflowService $workflow){}
 private function query(Request $r){return AffectedFamily::query()->with(['dafacRecord','barangay','disaster','evacuationCenter'])->when($r->filled('disaster_id'),fn($q)=>$q->where('disaster_id',$r->integer('disaster_id')))->when($r->filled('barangay_id'),fn($q)=>$q->where('barangay_id',$r->integer('barangay_id')))->when($r->filled('evacuation_center_id'),fn($q)=>$q->where('evacuation_center_id',$r->integer('evacuation_center_id')))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->when($r->filled('date_from'),fn($q)=>$q->whereDate('created_at','>=',$r->date_from))->when($r->filled('date_to'),fn($q)=>$q->whereDate('created_at','<=',$r->date_to));}
 private function filters(){return ['disasters'=>Disaster::orderByDesc('incident_date')->get(),'barangays'=>Barangay::orderBy('name')->get(),'centers'=>EvacuationCenter::orderBy('name')->get()];}
 public function dashboard(Request $r){
  $base=$this->query($r);
  $statusCounts=(clone $base)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate','status');
  $statuses=collect(FamilyStatus::cases())->mapWithKeys(fn($s)=>[$s->value=>(int)($statusCounts[$s->value]??0)]);
  $people=PersonAffected::query()->when($r->filled('disaster_id'),fn($q)=>$q->where(fn($q)=>$q->whereHas('affectedFamily',fn($f)=>$f->where('disaster_id',$r->integer('disaster_id')))->orWhereHas('evacuationCenter',fn($c)=>$c->where('disaster_id',$r->integer('disaster_id')))))->when($r->filled('barangay_id'),function($q)use($r){$barangay=Barangay::find($r->integer('barangay_id'));$q->where(fn($q)=>$q->whereHas('affectedFamily',fn($f)=>$f->where('barangay_id',$r->integer('barangay_id')))->orWhere('barangay',$barangay?->name));})->when($r->filled('evacuation_center_id'),fn($q)=>$q->where(fn($q)=>$q->where('evacuation_center_id',$r->integer('evacuation_center_id'))->orWhereHas('affectedFamily',fn($f)=>$f->where('evacuation_center_id',$r->integer('evacuation_center_id')))))->when($r->filled('date_from'),fn($q)=>$q->whereDate('created_at','>=',$r->date_from))->when($r->filled('date_to'),fn($q)=>$q->whereDate('created_at','<=',$r->date_to));
  $countByAge=fn(int $from,?int $to=null)=>(clone $people)->where('age','>=',$from)->when($to!==null,fn($q)=>$q->where('age','<=',$to))->distinct('control_number')->count('control_number');
  $countByCode=fn(array $codes)=>(clone $people)->whereIn(DB::raw('UPPER(code)'),$codes)->distinct('control_number')->count('control_number');
  $quickView=['total'=>(clone $people)->distinct('control_number')->count('control_number'),'male'=>(clone $people)->where('sex','Male')->distinct('control_number')->count('control_number'),'female'=>(clone $people)->where('sex','Female')->distinct('control_number')->count('control_number'),'age_0_4'=>$countByAge(0,4),'age_5_17'=>$countByAge(5,17),'age_18_59'=>$countByAge(18,59),'age_60_plus'=>$countByAge(60),'pwd'=>$countByCode(['PWD','B']),'pregnant'=>$countByCode(['PREG','D']),'lactating'=>$countByCode(['LM','E']),'solo_parent'=>$countByCode(['SP']),'four_ps'=>$countByCode(['4PS'])];
  $quickViewColumns=['total'=>['Total Individuals','ki-people','primary'],'male'=>['Male','ki-user','info'],'female'=>['Female','ki-user','danger'],'age_0_4'=>['Age 0–4','ki-calendar','success'],'age_5_17'=>['Age 5–17','ki-calendar','warning'],'age_18_59'=>['Age 18–59','ki-calendar','primary'],'age_60_plus'=>['Age 60+','ki-calendar','info'],'pwd'=>['Person with Disability','ki-accessibility','danger'],'pregnant'=>['Pregnant Women','ki-heart','warning'],'lactating'=>['Lactating Women','ki-heart','info'],'solo_parent'=>['Solo Parent','ki-profile-user','primary'],'four_ps'=>['4PS','ki-people','success']];
  $selectedQuickColumns=array_values(array_intersect(array_keys($quickViewColumns),(array)$r->input('quick_columns',array_keys($quickViewColumns))));
  if($selectedQuickColumns===[])$selectedQuickColumns=array_keys($quickViewColumns);
  $checkedQuickColumns=$r->has('quick_columns')?$selectedQuickColumns:[];
  $quickPeople=(clone $people)->with(['affectedFamily.barangay','affectedFamily.disaster','affectedFamily.evacuationCenter','evacuationCenter']);
  $selectedDemographics=array_values(array_diff($selectedQuickColumns,['total']));
  if($selectedDemographics!==[]){
   $quickPeople->where(function($q)use($selectedDemographics){
    if(in_array('male',$selectedDemographics,true))$q->orWhere('sex','Male');
    if(in_array('female',$selectedDemographics,true))$q->orWhere('sex','Female');
    if(in_array('age_0_4',$selectedDemographics,true))$q->orWhereBetween('age',[0,4]);
    if(in_array('age_5_17',$selectedDemographics,true))$q->orWhereBetween('age',[5,17]);
    if(in_array('age_18_59',$selectedDemographics,true))$q->orWhereBetween('age',[18,59]);
    if(in_array('age_60_plus',$selectedDemographics,true))$q->orWhere('age','>=',60);
    if(in_array('pwd',$selectedDemographics,true))$q->orWhereIn(DB::raw('UPPER(code)'),['PWD','B']);
    if(in_array('pregnant',$selectedDemographics,true))$q->orWhereIn(DB::raw('UPPER(code)'),['PREG','D']);
    if(in_array('lactating',$selectedDemographics,true))$q->orWhereIn(DB::raw('UPPER(code)'),['LM','E']);
    if(in_array('solo_parent',$selectedDemographics,true))$q->orWhereIn(DB::raw('UPPER(code)'),['SP']);
    if(in_array('four_ps',$selectedDemographics,true))$q->orWhereIn(DB::raw('UPPER(code)'),['4PS']);
   });
  }
  $quickPeople=$quickPeople->orderBy('full_name')->paginate(15,['*'],'quick_page')->withQueryString();
  $metrics=['TOTAL'=>$statusCounts->sum()]+$statuses->all();
  $metrics['FAMILY_AFFECTED']=(clone $people)->familyHeads()->distinct('control_number')->count('control_number');
  $metrics['PERSON_AFFECTED']=$quickView['total'];
  $metrics['RELEASED_PAYOUTS']=PayoutRelease::where('status','Released')->whereIn('affected_family_id',(clone $base)->select('affected_families.id'))->distinct('affected_family_id')->count('affected_family_id');
  $metrics['ASSIGNED_FAMILIES']=EvacuationCenterAssignment::where('status','ACTIVE')->count()+PersonAffected::familyHeads()->whereNull('affected_family_id')->whereNotNull('evacuation_center_id')->count();
  $metrics['ACTIVE_EVACUATION_CENTERS']=EvacuationCenter::where('is_active',true)->where('status','ACTIVE')->count();
  return response()->view('dashboard.index',$this->filters()+compact('metrics','quickView','quickViewColumns','selectedQuickColumns','checkedQuickColumns','quickPeople')+['page_title'=>'Disaster Operations Dashboard','page_description'=>'Live family assistance, evacuation, validation, and payout operations.'])->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0')->header('Pragma','no-cache')->header('Expires','0');
 }
 public function evacuationMap(){
  $centers=EvacuationCenter::query()
   ->with('barangay')
   ->withCount([
    'activeAssignments as assigned_families_count',
    'unlinkedPersonAffecteds as tciss_families_count'=>fn($q)=>$q->familyHeads(),
   ])
   ->where('is_active',true)
   ->where('status','ACTIVE')
   ->orderBy('name')
   ->get()
   ->each(fn($center)=>$center->setAttribute('live_family_count',$center->assigned_families_count+$center->tciss_families_count));

  return response()->view('dashboard.evacuation-map',compact('centers')+[
   'page_title'=>'Evacuation Center Map',
   'page_description'=>'Live viewing of evacuation areas and assigned families.',
  ])->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
 }
 public function evacuationMapDisplay(){
  return response()->view('dashboard.evacuation-map-display')
   ->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
 }
 public function evacuationMapCenters(){
  $centers=EvacuationCenter::query()
   ->with('barangay:id,name')
   ->with([
    'activeAssignments.family'=>fn($q)=>$q->withCount('familyMembers'),
    'unlinkedPersonAffecteds'=>fn($q)=>$q->familyHeads()->withCount('familyMembers'),
   ])
   ->withCount([
    'activeAssignments as assigned_families_count',
    'unlinkedPersonAffecteds as tciss_families_count'=>fn($q)=>$q->familyHeads(),
   ])
   ->whereNotNull('latitude')
   ->whereNotNull('longitude')
   ->get()
   ->filter(fn($center)=>is_finite((float)$center->latitude)
    && is_finite((float)$center->longitude)
    && (float)$center->latitude>=-90 && (float)$center->latitude<=90
    && (float)$center->longitude>=-180 && (float)$center->longitude<=180)
   ->map(function($center){
    $linkedIndividuals=$center->activeAssignments->sum(fn($assignment)=>$assignment->family?1+$assignment->family->family_members_count:0);
    $tcissIndividuals=$center->unlinkedPersonAffecteds->sum(fn($person)=>1+$person->family_members_count);
    return [
     'id'=>$center->id,
     'name'=>$center->name,
     'latitude'=>(float)$center->latitude,
     'longitude'=>(float)$center->longitude,
     'address'=>$center->address,
     'barangay'=>$center->barangay?->name,
     'capacity'=>$center->capacity,
     'status'=>$center->status,
     'is_active'=>$center->is_active,
     'family_count'=>$center->assigned_families_count+$center->tciss_families_count,
     'individual_count'=>$linkedIndividuals+$tcissIndividuals,
    ];
   })->values();

  return response()->json(['data'=>$centers,'updated_at'=>now()->toIso8601String()])
   ->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
 }
 public function duplicates(Request $r){$families=$this->query($r)->with(['duplicateChecks.possibleDuplicateFamily','familyMembers'])->whereIn('status',[FamilyStatus::DUPLICATE_CHECK_PENDING,FamilyStatus::POSSIBLE_DUPLICATE])->latest()->paginate(20)->withQueryString();return view('disaster.duplicates',compact('families')+['page_title'=>'Duplicate Checking','page_description'=>'Review connected DAFAC household records.']);}
 public function resolveDuplicate(Request $r,AffectedFamily $family){$data=$r->validate(['resolution'=>['required',Rule::in(['clear','possible','confirm','separate'])],'remarks'=>['nullable','string','max:2000']]);$target=match($data['resolution']){'possible'=>FamilyStatus::POSSIBLE_DUPLICATE,'confirm'=>FamilyStatus::DUPLICATE_CONFIRMED,default=>FamilyStatus::DUPLICATE_CLEARED};$check=$family->duplicateChecks()->latest()->first()??$family->duplicateChecks()->create(['match_score'=>0,'matched_fields'=>[]]);$check->update(['resolution'=>$target===FamilyStatus::DUPLICATE_CONFIRMED?'Duplicate':($data['resolution']==='separate'?'Separate Household':'Resolved'),'resolved_by'=>$r->user()->id,'resolved_at'=>now()]);if($target===FamilyStatus::DUPLICATE_CONFIRMED&&$family->status===FamilyStatus::DUPLICATE_CHECK_PENDING)$family=$this->workflow->transition($family,FamilyStatus::POSSIBLE_DUPLICATE,$r->user(),'possible_duplicate_flagged',$data['remarks']??null);$this->workflow->transition($family,$target,$r->user(),'duplicate_check_resolved',$data['remarks']??null,['resolution'=>$data['resolution']]);if($target===FamilyStatus::DUPLICATE_CLEARED)$this->workflow->transition($family->refresh(),FamilyStatus::VALIDATION_PENDING,$r->user(),'validation_queued');return back()->with('success','Duplicate review saved.');}
 public function validations(Request $r){$families=$this->query($r)->with('validationRecords')->whereIn('status',[FamilyStatus::DUPLICATE_CLEARED,FamilyStatus::VALIDATION_PENDING,FamilyStatus::NEEDS_CORRECTION])->latest()->paginate(20)->withQueryString();return view('disaster.validation',compact('families')+['page_title'=>'Validation','page_description'=>'Validate connected household records.']);}
 public function validateFamily(Request $r,AffectedFamily $family){$data=$r->validate(['decision'=>['required',Rule::in(['approve','correction','reject'])],'house_ownership'=>['required',Rule::in(['Owner','Renter','Sharer'])],'housing_condition'=>['required',Rule::in(['Totally Damaged','Partially Damaged','Water Damage'])],'notes'=>['nullable','string','max:3000']]);if($family->status===FamilyStatus::DUPLICATE_CLEARED)$family=$this->workflow->transition($family,FamilyStatus::VALIDATION_PENDING,$r->user(),'validation_started');$target=match($data['decision']){'approve'=>FamilyStatus::VALIDATED,'correction'=>FamilyStatus::NEEDS_CORRECTION,default=>FamilyStatus::REJECTED};DB::transaction(function()use($family,$data,$target,$r){$family->update(['house_ownership'=>$data['house_ownership'],'housing_condition'=>$data['housing_condition']]);ValidationRecord::create(['affected_family_id'=>$family->id,'validated_house_ownership'=>$data['house_ownership'],'validated_housing_condition'=>$data['housing_condition'],'notes'=>$data['notes']??null,'status'=>match($target){FamilyStatus::VALIDATED=>'Validated',FamilyStatus::REJECTED=>'Rejected',default=>'Needs Correction'},'validated_by'=>$r->user()->id,'validated_at'=>now()]);$this->workflow->transition($family->refresh(),$target,$r->user(),'validation_'.$data['decision'],$data['notes']??null);});return back()->with('success','Validation saved.');}
 public function payroll(Request $r){$families=$this->query($r)->with(['familyMembers','postPayoutRequirement.documents','payoutReleases'=>fn($q)=>$q->where('status','Released')->with(['releaser','center'])->latest('released_at')])->whereHas('payoutReleases',fn($q)=>$q->where('status','Released'))->when($r->filled('search'),function($q)use($r){$s='%'.$r->string('search').'%';$q->where(fn($q)=>$q->where('household_head_surname','like',$s)->orWhere('household_head_given_name','like',$s)->orWhere('complete_address','like',$s)->orWhereHas('dafacRecord',fn($d)=>$d->where('reference_number','like',$s)));})->orderByDesc(PayoutRelease::select('released_at')->whereColumn('affected_family_id','affected_families.id')->where('status','Released')->latest('released_at')->limit(1))->paginate(30)->withQueryString();$totalReleased=PayoutRelease::where('status','Released')->count();$totalAmount=PayoutRelease::where('status','Released')->sum('amount');return view('disaster.payroll',compact('families','totalReleased','totalAmount')+$this->filters()+['page_title'=>'Released Payouts','page_description'=>'View households that have received their financial assistance.']);}
 public function payrollPhoto(PayoutRelease $release){abort_unless($release->status==='Released'&&$release->payout_photo_path&&Storage::disk('local')->exists($release->payout_photo_path),404);return Storage::disk('local')->response($release->payout_photo_path,'payout-proof-'. $release->id,['Cache-Control'=>'private, max-age=300']);}
 public function payrollRequirements(Request $r,AffectedFamily $family){abort_unless($family->payoutReleases()->where('status','Released')->exists(),404,'No released payout exists for this household.');$data=$r->validate(['valid_id_document'=>['nullable','file','mimes:jpg,jpeg,png,pdf','max:8192'],'barangay_document'=>['nullable','file','mimes:jpg,jpeg,png,pdf','max:8192']]);abort_if(!$r->hasFile('valid_id_document')&&!$r->hasFile('barangay_document'),422,'Select at least one requirement document.');$requirement=PostPayoutRequirement::firstOrCreate(['affected_family_id'=>$family->id]);foreach(['valid_id_document'=>'bfp_certificate_status','barangay_document'=>'barangay_certification_status'] as $type=>$statusField)if($r->hasFile($type)){$file=$r->file($type);$path=$file->store('requirement-documents','local');$requirement->documents()->create(['document_type'=>$type,'file_path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'uploaded_by'=>$r->user()->id]);$requirement->update([$statusField=>'Submitted']);}$requirement->load('documents');return response()->json(['success'=>true,'message'=>'Requirements uploaded successfully.','data'=>['valid_id_status'=>$requirement->bfp_certificate_status,'barangay_status'=>$requirement->barangay_certification_status,'valid_id_name'=>$requirement->documents->where('document_type','valid_id_document')->sortByDesc('id')->first()?->original_name,'barangay_name'=>$requirement->documents->where('document_type','barangay_document')->sortByDesc('id')->first()?->original_name]]);}
 public function payrollAction(Request $r){$data=$r->validate(['family_ids'=>['required','array','min:1'],'family_ids.*'=>['exists:affected_families,id'],'action'=>['required',Rule::in(['ready','submit'])],'amount'=>['nullable','numeric','min:0']]);DB::transaction(function()use($data,$r){$batch=null;if($data['action']==='submit')$batch=PayrollBatch::create(['reference_number'=>'PAY-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)),'payroll_date'=>today(),'status'=>'SUBMITTED','prepared_by'=>$r->user()->id,'submitted_at'=>now()]);foreach(AffectedFamily::whereIn('id',$data['family_ids'])->lockForUpdate()->get() as $family){$target=$data['action']==='ready'?FamilyStatus::PAYROLL_READY:FamilyStatus::SUBMITTED_FOR_PAYROLL;$family=$this->workflow->transition($family,$target,$r->user(),'payroll_'.$data['action']);$record=PayrollRecord::updateOrCreate(['affected_family_id'=>$family->id],['dafac_record_id'=>$family->dafacRecord->id,'payroll_batch_id'=>$batch?->id,'amount'=>$data['amount']??0,'status'=>$data['action']==='submit'?'SUBMITTED':'READY']);if($batch)$this->workflow->transition($family->refresh(),FamilyStatus::PAYOUT_PENDING,$r->user(),'payout_queued');}if($batch)$batch->update(['total_amount'=>$batch->records()->sum('amount')]);});return back()->with('success','Payroll records updated.');}
 public function requirements(Request $r){$requirements=PostPayoutRequirement::with(['affectedFamily.dafacRecord','affectedFamily.barangay'])->whereHas('affectedFamily',fn($q)=>$q->whereIn('status',[FamilyStatus::ASSISTANCE_RELEASED,FamilyStatus::REQUIREMENTS_PENDING,FamilyStatus::REQUIREMENTS_COMPLETED]))->latest()->paginate(20);return view('disaster.requirements',compact('requirements')+['page_title'=>'Post-Payout Requirements','page_description'=>'Verify mandatory documents for released households.']);}
 public function verifyRequirements(Request $r,PostPayoutRequirement $requirement){$data=$r->validate(['bfp_certificate_status'=>['required',Rule::in(['Pending','Submitted','Verified'])],'barangay_certification_status'=>['required',Rule::in(['Pending','Submitted','Verified'])],'notes'=>['nullable','string','max:3000'],'valid_id_document'=>['nullable','file','mimes:jpg,jpeg,png,pdf','max:8192'],'barangay_document'=>['nullable','file','mimes:jpg,jpeg,png,pdf','max:8192']]);$requirement->update(collect($data)->except(['valid_id_document','barangay_document'])->all()+['verified_by'=>$r->user()->id,'verified_at'=>now()]);foreach(['valid_id_document','barangay_document'] as $type)if($r->hasFile($type)){$file=$r->file($type);$requirement->documents()->create(['document_type'=>$type,'file_path'=>$file->store('requirement-documents','local'),'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'uploaded_by'=>$r->user()->id]);}if($requirement->isComplete())$this->workflow->transition($requirement->affectedFamily,FamilyStatus::REQUIREMENTS_COMPLETED,$r->user(),'requirements_completed');return back()->with('success','Requirements updated.');}
 public function reports(Request $r){
  [$rows,$columns,$selected,$incident]=$this->monitoringReportData($r);
  $checkedColumns=$r->has('columns')?$selected:[];
  return view('disaster.reports',compact('rows','columns','selected','checkedColumns','incident')+$this->filters()+['page_title'=>'Reports','page_description'=>'Configure and export the evacuee monitoring report.']);
 }
 public function exportReport(Request $r){
  [$rows,$columns,$selected,$incident]=$this->monitoringReportData($r);
  $name='evacuee-monitoring-report-'.now()->format('Y-m-d-His').'.xlsx';
  return Excel::download(new EvacueeMonitoringReportExport($rows,$columns,$selected,$incident),$name);
 }
 private function monitoringReportData(Request $r):array {
  $columns=EvacueeMonitoringReportExport::columns();
  $selected=array_values(array_intersect(array_keys($columns),(array)$r->input('columns',array_keys($columns))));
  if($selected===[])$selected=array_keys($columns);
  $incident=$r->filled('disaster_id')?Disaster::find($r->integer('disaster_id')):null;
  $centers=EvacuationCenter::query()->with(['barangay','affectedFamilies'=>function($q)use($r,$incident){$q->with('familyMembers')->when($incident,fn($q)=>$q->where('disaster_id',$incident->id))->when($r->filled('date_from'),fn($q)=>$q->whereDate('created_at','>=',$r->date_from))->when($r->filled('date_to'),fn($q)=>$q->whereDate('created_at','<=',$r->date_to));},'unlinkedPersonAffecteds'=>function($q)use($r){$q->with('familyMembers')->when($r->filled('date_from'),fn($q)=>$q->whereDate('created_at','>=',$r->date_from))->when($r->filled('date_to'),fn($q)=>$q->whereDate('created_at','<=',$r->date_to));}])->when($incident,fn($q)=>$q->where(fn($q)=>$q->where('disaster_id',$incident->id)->orWhereHas('affectedFamilies',fn($f)=>$f->where('disaster_id',$incident->id))))->when($r->filled('barangay_id'),fn($q)=>$q->where('barangay_id',$r->integer('barangay_id')))->when($r->filled('evacuation_center_id'),fn($q)=>$q->whereKey($r->integer('evacuation_center_id')))->when($r->filled('district'),fn($q)=>$q->where(fn($q)=>$q->where('district',$r->district)->orWhereHas('barangay',fn($b)=>$b->where('district',$r->district))))->where('is_active',true)->orderBy('district')->orderBy('name')->get();
  $rows=$centers->map(function($center){
   $families=$center->affectedFamilies; $externalFamilies=$center->unlinkedPersonAffecteds; $members=$families->flatMap->familyMembers;
   $people=$members->map(fn($m)=>['age'=>$m->age,'sex'=>$m->sex,'remarks'=>strtoupper((string)$m->remarks_codes)]);
   foreach($families as $family)$people->push(['age'=>$family->age,'sex'=>null,'remarks'=>'']);
   foreach($externalFamilies as $family){$people->push(['age'=>$family->age,'sex'=>$family->sex,'remarks'=>strtoupper((string)$family->code)]);foreach($family->familyMembers as $member)$people->push(['age'=>$member->age,'sex'=>$member->sex,'remarks'=>strtoupper((string)$member->code)]);}
   $remarks=fn(array $codes)=>$people->filter(fn($person)=>in_array($person['remarks'],$codes,true))->count();
   return ['district'=>$center->district?:($center->barangay?->district?:'Unassigned'),'barangay'=>$center->barangay?->name?:'Unassigned','evacuation_center'=>$center->name,'families'=>$families->count()+$externalFamilies->count(),'individuals'=>$people->count(),'male'=>$people->where('sex','Male')->count(),'female'=>$people->where('sex','Female')->count(),'age_0_4'=>$people->whereBetween('age',[0,4])->count(),'age_5_17'=>$people->whereBetween('age',[5,17])->count(),'age_18_59'=>$people->whereBetween('age',[18,59])->count(),'age_60_plus'=>$people->where('age','>=',60)->count(),'pwd'=>$remarks(['PWD','B']),'solo_parent'=>$remarks(['SP']),'lactating'=>$remarks(['LM','E']),'pregnant'=>$remarks(['PREG','D']),'four_ps'=>$remarks(['4PS']),'staff'=>0];
  });
  return [$rows,$columns,$selected,$incident];
 }
 public function show(AffectedFamily $family){$family->load(['disaster','barangay','evacuationCenter','dafacRecord.documents','familyMembers','duplicateChecks.possibleDuplicateFamily','validationRecords.documents','payrollRecord','payoutReleases','postPayoutRequirement.documents','workflowHistories.performer','uploadedDocuments','auditLogs']);return view('disaster.family-show',compact('family')+['page_title'=>$family->dafacRecord?->reference_number??'Affected Family','page_description'=>'Complete connected household record.']);}
}
