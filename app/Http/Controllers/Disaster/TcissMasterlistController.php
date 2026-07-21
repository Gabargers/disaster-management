<?php

namespace App\Http\Controllers\Disaster;

use App\Http\Controllers\Controller;
use App\Enums\FamilyStatus;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\TcissMasterlistRecord;
use App\Models\Disaster\UploadedDocument;
use App\Services\Disaster\DafacIntakeIntegrationService;
use App\Services\Disaster\DisasterAssistanceWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TcissMasterlistController extends Controller
{
    public function __construct(
        private DafacIntakeIntegrationService $integration,
        private DisasterAssistanceWorkflowService $workflow
    ) {}
    public function index(Request $request)
    {
        $records = TcissMasterlistRecord::query()
            ->with(['barangay', 'evacuationCenter', 'dafacRecord', 'affectedFamily.disaster', 'affectedFamily.familyMembers', 'affectedFamily.payoutReleases'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(function ($query) use ($search) {
                    $query->where('household_head_full_name', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhere('source_reference', 'like', $search);
                });
            })
            ->when($request->filled('barangay_id'), fn ($query) => $query->where('barangay_id', $request->integer('barangay_id')))
            ->when($request->filled('evacuation_center_id'), fn ($query) => $query->where('evacuation_center_id', $request->integer('evacuation_center_id')))
            ->when($request->filled('disaster_id'), fn ($query) => $query->whereHas('affectedFamily', fn($q)=>$q->where('disaster_id',$request->integer('disaster_id'))))
            ->when($request->filled('verification_status'), fn ($query) => $query->where('verification_status',$request->verification_status))
            ->when($request->filled('payout_status'), fn ($query) => $query->whereHas('affectedFamily.payoutReleases', fn($q)=>$q->where('status',$request->payout_status)))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at','>=',$request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at','<=',$request->date_to))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('disaster.tciss', [
            'page_title' => 'TCISS / Masterlist Verification',
            'page_description' => 'Verify affected families before DAFAC intake.',
            'records' => $records,
            'barangays' => \App\Models\Cms\Barangay::query()->where('is_active', true)->orderBy('name')->get(),
            'evacuationCenters' => \App\Models\Disaster\EvacuationCenter::query()->where('is_active', true)->orderBy('name')->get(),
            'disasters' => \App\Models\Disaster\Disaster::query()->orderByDesc('incident_date')->get(),
        ]);
    }

    public function fullDetails(TcissMasterlistRecord $record): JsonResponse
    {
        $record->load([
            'barangay', 'evacuationCenter', 'verifier',
            'affectedFamily.disaster', 'affectedFamily.barangay', 'affectedFamily.evacuationCenter',
            'affectedFamily.familyMembers', 'affectedFamily.dafacRecord.interviewer', 'affectedFamily.dafacRecord.validator',
            'affectedFamily.evacuationCenterAssignments.center.barangay', 'affectedFamily.evacuationCenterAssignments.assigner',
            'affectedFamily.validationRecords.validator', 'affectedFamily.validationRecords.documents',
            'affectedFamily.duplicateChecks.possibleDuplicateFamily', 'affectedFamily.duplicateChecks.resolver',
            'affectedFamily.assistanceRecords.releaser', 'affectedFamily.payoutReleases.payoutSchedule',
            'affectedFamily.payoutReleases.releaser', 'affectedFamily.postPayoutRequirement.documents',
        ]);

        $family = $record->affectedFamily;
        $dafac = $family?->dafacRecord;
        $validation = $family?->validationRecords->sortByDesc('validated_at')->first();
        $documents = collect($family?->validationRecords ?? [])->flatMap->documents
            ->merge($family?->postPayoutRequirement?->documents ?? collect());

        return response()->json(['success' => true, 'data' => [
            'masterlist' => [
                'id' => $record->id, 'reference_number' => $record->source_reference, 'source' => $record->source,
                'verification_status' => $record->verification_status,
                'can_verify' => $record->verification_status !== 'Verified',
                'verify_url' => route('disaster.tciss.verify', $record),
                'verified_by' => $record->verifier?->name, 'verified_at' => $record->verified_at?->toIso8601String(),
                'created_at' => $record->created_at?->toIso8601String(), 'updated_at' => $record->updated_at?->toIso8601String(),
            ],
            'affected_family' => $family ? [
                'full_name' => $family->household_head_full_name, 'surname' => $family->household_head_surname,
                'given_name' => $family->household_head_given_name, 'middle_name' => $family->household_head_middle_name,
                'birthdate' => $family->birthdate?->format('Y-m-d'), 'age' => $family->birthdate?->age,
                'sex' => null, 'occupation' => $family->occupation, 'monthly_income' => $family->monthly_income,
                'contact_number' => null, 'complete_address' => $family->complete_address,
                'house_ownership' => $family->house_ownership, 'housing_condition' => $family->housing_condition,
                'health_condition' => $family->health_condition, 'status' => $family->status?->value ?? $family->status,
                'member_count' => $family->familyMembers->count(),
                'disaster_name' => $family->disaster?->name, 'disaster_type' => $family->disaster?->type,
                'incident_date' => $family->disaster?->incident_date?->format('Y-m-d'),
                'barangay' => $family->barangay?->name, 'evacuation_center' => $family->evacuationCenter?->name,
            ] : null,
            'dafac' => $dafac ? [
                'reference_number' => $dafac->reference_number,
                'interview_date' => $dafac->interview_date?->format('Y-m-d'),
                'interviewed_by' => $dafac->interviewer?->name, 'validated_by' => $dafac->validator?->name,
            ] : null,
            'family_members' => $family?->familyMembers->map(fn ($member) => [
                'name' => $member->name, 'birthdate' => $member->birthdate?->format('Y-m-d'),
                'age' => $member->birthdate?->age, 'relationship' => $member->relationship_to_head,
                'sex' => $member->sex, 'occupation' => $member->occupation, 'monthly_income' => $member->monthly_income,
                'health_condition' => $member->health_condition, 'remarks_code' => $member->remarks_codes,
            ])->values() ?? [],
            'validation' => $validation ? [
                'status' => $validation->status, 'house_ownership' => $validation->validated_house_ownership,
                'housing_condition' => $validation->validated_housing_condition, 'notes' => $validation->notes,
                'validated_by' => $validation->validator?->name, 'validated_at' => $validation->validated_at?->toIso8601String(),
            ] : null,
            'duplicate_checks' => $family?->duplicateChecks->map(fn ($check) => [
                'possible_duplicate_found' => (bool) $check->possible_duplicate_family_id, 'match_score' => $check->match_score,
                'match_reason' => collect($check->matched_fields)->implode(', '),
                'matched_household' => $check->possibleDuplicateFamily?->household_head_full_name,
                'resolution_status' => $check->resolution, 'resolved_by' => $check->resolver?->name,
                'resolved_at' => $check->resolved_at?->toIso8601String(),
            ])->values() ?? [],
            'attachments' => $documents->map(fn ($document) => [
                'type' => $document->document_type, 'name' => $document->original_name ?: 'Attachment',
                'mime_type' => $document->mime_type,
                'url' => URL::temporarySignedRoute('disaster.tciss.documents.show', now()->addMinutes(10), $document),
            ])->values(),
            'assistance_history' => $family?->assistanceRecords->map(fn ($assistance) => [
                'date' => $assistance->date_assistance_provided?->format('Y-m-d'), 'kind' => $assistance->assistance_kind,
                'quantity_amount' => $assistance->quantity_amount, 'provider' => $assistance->provider,
                'released_by' => $assistance->releaser?->name,
            ])->values() ?? [],
            'payout' => $family?->payoutReleases->map(fn ($payout) => [
                'schedule' => $payout->payoutSchedule?->title, 'scheduled_date' => $payout->payoutSchedule?->scheduled_date?->format('Y-m-d'),
                'status' => $payout->status, 'released_by' => $payout->releaser?->name,
                'released_at' => $payout->released_at?->toIso8601String(),
            ])->values() ?? [],
            'assignment' => $this->assignmentData($record),
        ]])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Vary', 'Cookie');
    }

    public function verify(Request $request, TcissMasterlistRecord $record): JsonResponse
    {
        if ($record->verification_status === 'Verified') {
            return response()->json(['success' => true, 'message' => 'This TCISS record is already verified.']);
        }

        DB::transaction(function () use ($request, $record) {
            $record = TcissMasterlistRecord::lockForUpdate()->findOrFail($record->id);
            if ($record->verification_status === 'Verified') return;

            $record->update([
                'verification_status' => 'Verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            $family = $record->affectedFamily;
            if ($family?->status === FamilyStatus::DRAFT) {
                $this->workflow->transition($family, FamilyStatus::TCISS_VERIFIED, $request->user(), 'tciss_verified');
            } elseif ($family) {
                $family->workflowHistories()->create([
                    'from_status' => $family->status->value,
                    'to_status' => $family->status->value,
                    'action' => 'tciss_verified',
                    'performed_by' => $request->user()->id,
                    'performed_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'auditable_type' => TcissMasterlistRecord::class,
                'auditable_id' => $record->id,
                'action' => 'tciss_verified',
                'new_values' => ['verification_status' => 'Verified'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'TCISS record verified. You may now assign an evacuation center.',
        ]);
    }

    public function assignEvacuationCenter(Request $request, TcissMasterlistRecord $record): JsonResponse
    {
        abort_unless($record->verification_status === 'Verified', 422, 'Verify the TCISS record before assigning an evacuation center.');
        abort_unless($record->affectedFamily, 422, 'This TCISS record is not linked to an affected family.');
        $data=$request->validate(['barangay_id'=>['required','integer','exists:barangays,id'],'evacuation_center_id'=>['required','integer','exists:evacuation_centers,id'],
            'assignment_date'=>['required','date','before_or_equal:today'],'notes'=>['nullable','string','max:1000'],'transfer_reason'=>['nullable','string','max:1000']]);
        $current=$record->affectedFamily->activeEvacuationCenterAssignment()->first(); $hadAssignment=(bool)$current;
        $isTransfer=$current && ($current->evacuation_center_id!==$data['evacuation_center_id'] || $record->affectedFamily->barangay_id!==$data['barangay_id']);
        if($isTransfer && blank($data['transfer_reason']??null)) throw \Illuminate\Validation\ValidationException::withMessages(['transfer_reason'=>'A transfer reason is required when changing evacuation centers.']);
        $reason=$isTransfer?$data['transfer_reason']:($data['notes']??'Assigned through TCISS.');
        $assignment=$this->integration->reassign($record->affectedFamily,$data['evacuation_center_id'],$request->user(),$reason,$data['barangay_id'],$data['assignment_date']);
        $record->refresh()->load(['affectedFamily.barangay','affectedFamily.evacuationCenter','affectedFamily.evacuationCenterAssignments.center.barangay','affectedFamily.evacuationCenterAssignments.assigner']);
        $noChange=$current && $current->id===$assignment->id;
        return response()->json(['success'=>true,'message'=>$noChange?'The family is already assigned to this evacuation center.':($isTransfer?'Evacuation Center transferred successfully.':'Evacuation Center assigned successfully.'),'data'=>[
            'assignment'=>$assignment->load(['center.barangay','assigner']), 'affected_family'=>$record->affectedFamily,
            'assignment_view'=>$this->assignmentData($record),
        ]]);
    }

    private function assignmentData(TcissMasterlistRecord $record): array
    {
        $family=$record->affectedFamily; if(!$family)return ['can_assign'=>false,'current'=>null,'centers'=>[],'history'=>[]];
        $history=$family->evacuationCenterAssignments->sortByDesc('assigned_at')->values();
        $current=$history->firstWhere('status','ACTIVE');
        $centers=\App\Models\Disaster\EvacuationCenter::where('barangay_id',$family->barangay_id)->where('disaster_id',$family->disaster_id)->where('is_active',true)->where('status','ACTIVE')->with('activeAssignments.family.familyMembers')->orderBy('name')->get();
        $lockReason=$record->verification_status!=='Verified'?'Verify the TCISS record before assigning an evacuation center.':($current && $family->payoutReleases()->where('status','Released')->exists()?'Assignment cannot be changed because the payout has already been released.':null);
        return [
            'can_assign'=>request()->user()->can('evacuation_center.assign_family')&&!$lockReason,
            'lock_reason'=>$lockReason,
            'assign_url'=>route('disaster.tciss.assign-evacuation-center',$record),
            'barangay'=>['id'=>$family->barangay_id,'name'=>$family->barangay?->name],
            'barangays'=>\App\Models\Cms\Barangay::where('is_active',true)->orderBy('name')->get(['id','name']),
            'disaster'=>['id'=>$family->disaster_id,'name'=>$family->disaster?->name],
            'household_head'=>$family->household_head_full_name,
            'current'=>$current?['id'=>$current->id,'center_id'=>$current->evacuation_center_id,'center'=>$current->center?->name,'barangay'=>$current->center?->barangay?->name,'assigned_by'=>$current->assigner?->name,'assigned_at'=>$current->assigned_at?->toIso8601String(),'status'=>$current->status]:null,
            'centers'=>$centers->map(fn($center)=>$this->centerOption($center,request()->user())),
            'history'=>$history->map(fn($assignment)=>['id'=>$assignment->id,'center'=>$assignment->center?->name,'barangay'=>$assignment->center?->barangay?->name,'status'=>$assignment->status,'assigned_at'=>$assignment->assigned_at?->toIso8601String(),'unassigned_at'=>$assignment->unassigned_at?->toIso8601String(),'assigned_by'=>$assignment->assigner?->name,'transfer_reason'=>$assignment->remarks]),
        ];
    }

    private function centerOption($center,$user): array
    {
        $occupied=$center->activeAssignments->sum(fn($assignment)=>1+$assignment->family->familyMembers->count()); $available=max(0,$center->capacity-$occupied);
        return ['id'=>$center->id,'name'=>$center->name,'status'=>$center->status,'capacity'=>$center->capacity,'occupied_count'=>$occupied,'available_slots'=>$available,
            'is_full'=>$available===0,'can_override'=>$user->can('evacuation_center.capacity_override')];
    }

    public function document(UploadedDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name ?: basename($document->file_path));
    }
}
