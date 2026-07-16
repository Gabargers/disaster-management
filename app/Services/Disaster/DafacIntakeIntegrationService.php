<?php

namespace App\Services\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Auth\User;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterAssignment;
use App\Models\Disaster\TcissMasterlistRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DafacIntakeIntegrationService
{
    public function __construct(private DisasterAssistanceWorkflowService $workflow) {}

    public function save(array $data, Request $request): DafacRecord
    {
        return DB::transaction(function () use ($data, $request) {
            $user = $request->user();
            $this->validateCenter($data);
            $head = $data['household_head'];
            $family = new AffectedFamily([
                'disaster_id'=>$data['disaster_id'], 'barangay_id'=>$data['barangay_id'], 'evacuation_center_id'=>$data['evacuation_center_id']??null,
                'household_head_surname'=>$head['surname'], 'household_head_given_name'=>$head['given_name'], 'household_head_middle_name'=>$head['middle_name']??null,
                'birthdate'=>$head['birthdate'], 'occupation'=>$head['occupation']??null, 'monthly_income'=>$head['monthly_income']??null,
                'contact_number'=>$head['contact_number']??null, 'complete_address'=>$head['complete_address'], 'house_ownership'=>$data['house_ownership'],
                'housing_condition'=>$data['housing_condition'], 'health_condition'=>$data['health_condition']??null, 'status'=>FamilyStatus::DRAFT,
                'created_by'=>$user->id, 'updated_by'=>$user->id,
            ]);
            if (AffectedFamily::where('exact_household_hash', $family->buildHouseholdHash())->lockForUpdate()->exists()) abort(409, 'A DAFAC intake already exists for this household, barangay, and disaster.');
            $family->save();
            foreach ($data['family_members']??[] as $member) $family->familyMembers()->create([
                'name'=>$member['full_name'], 'birthdate'=>$member['birthdate'], 'relationship_to_head'=>$member['relationship_to_head'], 'sex'=>$member['sex'],
                'occupation'=>$member['occupation']??null, 'monthly_income'=>$member['monthly_income']??null, 'health_condition'=>$member['health_condition']??null,
                'remarks_codes'=>$member['remarks_code']??null,
            ]);
            $dafac = $family->dafacRecord()->create(['reference_number'=>$this->nextReference('DAFAC', DafacRecord::class, 'reference_number'),
                'interview_date'=>$data['intake_date'], 'interviewed_by'=>$user->id, 'interviewed_by_name'=>$data['interviewed_by'], 'attestation_confirmed'=>true]);
            foreach (['signature','thumbmark'] as $type) if ($request->hasFile($type)) {
                $file=$request->file($type); $path=$file->store("dafac/{$dafac->uuid}",'local');
                $dafac->documents()->create(['document_type'=>$type,'file_path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'uploaded_by'=>$user->id]);
            }
            $tciss=TcissMasterlistRecord::updateOrCreate(['affected_family_id'=>$family->id],[
                'dafac_record_id'=>$dafac->id, 'barangay_id'=>$family->barangay_id, 'evacuation_center_id'=>$family->evacuation_center_id,
                'household_head_full_name'=>$family->household_head_full_name, 'birthdate'=>$family->birthdate, 'address'=>$family->complete_address,
                'source_reference'=>$this->nextReference('TCISS', TcissMasterlistRecord::class, 'source_reference'), 'source'=>'DAFAC_INTAKE', 'verification_status'=>'Needs Review',
            ]);
            $assignment=$this->syncAssignment($family, $family->evacuation_center_id, $user, 'Selected during DAFAC intake.');
            AuditLog::create(['user_id'=>$user->id,'auditable_type'=>DafacRecord::class,'auditable_id'=>$dafac->id,'action'=>'dafac_intake_created',
                'new_values'=>['reference_number'=>$dafac->reference_number,'affected_family_id'=>$family->id,'tciss_masterlist_id'=>$tciss->id,'evacuation_center_assignment_id'=>$assignment?->id],
                'ip_address'=>$request->ip(),'user_agent'=>substr((string)$request->userAgent(),0,1000)]);
            $family=$this->workflow->transition($family,FamilyStatus::DAFAC_INTAKE_COMPLETED,$user,'dafac_intake_completed');
            $possible=AffectedFamily::whereKeyNot($family->id)->where('disaster_id',$family->disaster_id)->where('barangay_id',$family->barangay_id)->where('birthdate',$family->birthdate)->where(fn($q)=>$q->where('household_head_surname',$family->household_head_surname)->orWhere('household_head_given_name',$family->household_head_given_name))->first();
            $family->duplicateChecks()->create(['possible_duplicate_family_id'=>$possible?->id,'match_score'=>$possible?80:0,'matched_fields'=>$possible?['disaster','barangay','birthdate','household head name']:[],'resolution'=>'Pending']);
            $this->workflow->transition($family,FamilyStatus::DUPLICATE_CHECK_PENDING,$user,'duplicate_check_queued',null,['possible_duplicate_family_id'=>$possible?->id]);
            return $dafac->load(['affectedFamily.barangay','affectedFamily.evacuationCenter','affectedFamily.tcissMasterlistRecord','affectedFamily.activeEvacuationCenterAssignment']);
        }, 3);
    }

    public function reassign(AffectedFamily $family, int $centerId, User $user, string $reason, ?int $barangayId=null, ?string $assignmentDate=null): EvacuationCenterAssignment
    {
        return DB::transaction(function () use ($family,$centerId,$user,$reason,$barangayId,$assignmentDate) {
            $family=AffectedFamily::lockForUpdate()->findOrFail($family->id);
            $targetBarangay=$barangayId??$family->barangay_id;
            $center=EvacuationCenter::whereKey($centerId)->where('is_active',true)->firstOrFail();
            if ($center->barangay_id!==$targetBarangay || $center->disaster_id!==$family->disaster_id) throw ValidationException::withMessages(['evacuation_center_id'=>'The evacuation center must belong to the selected barangay and family disaster.']);
            if ($center->status!=='ACTIVE') throw ValidationException::withMessages(['evacuation_center_id'=>'The selected evacuation center is closed or inactive.']);
            $previous=$family->activeEvacuationCenterAssignment()->with('center')->first();
            if ($previous?->evacuation_center_id===$center->id && $family->barangay_id===$targetBarangay) return $previous;
            if ($previous && $family->payoutReleases()->where('status','Released')->exists()) abort(409,'A released payout cannot be transferred without an authorized correction process.');
            $assignedPeople=$center->activeAssignments()->with('family.familyMembers')->get()->sum(fn($assignment)=>1+$assignment->family->familyMembers->count());
            $householdSize=1+$family->familyMembers()->count();
            if ($assignedPeople+$householdSize>$center->capacity && !$user->can('evacuation_center.capacity_override')) throw ValidationException::withMessages(['evacuation_center_id'=>'This evacuation center has insufficient capacity. An authorized administrator may override this limit.']);
            $assignment=$this->syncAssignment($family,$center->id,$user,$reason,$assignmentDate);
            $family->update(['barangay_id'=>$targetBarangay,'evacuation_center_id'=>$center->id,'updated_by'=>$user->id]);
            $family->tcissMasterlistRecord?->update(['barangay_id'=>$targetBarangay,'evacuation_center_id'=>$center->id]);
            AuditLog::create(['user_id'=>$user->id,'auditable_type'=>AffectedFamily::class,'auditable_id'=>$family->id,'action'=>'evacuation_center_reassigned','new_values'=>['evacuation_center_id'=>$center->id,'reason'=>$reason]]);
            $family->workflowHistories()->create(['from_status'=>$family->status->value,'to_status'=>$family->status->value,'action'=>$previous?'evacuation_center_transferred':'evacuation_center_assigned','remarks'=>$reason,'performed_by'=>$user->id,'performed_at'=>now(),'metadata'=>['old_center_id'=>$previous?->evacuation_center_id,'new_center_id'=>$center->id,'assignment_id'=>$assignment->id]]);
            return $assignment;
        });
    }

    private function syncAssignment(AffectedFamily $family, ?int $centerId, User $user, string $remarks, ?string $assignmentDate=null): ?EvacuationCenterAssignment
    {
        if (!$centerId) return null;
        $active=EvacuationCenterAssignment::where('affected_family_id',$family->id)->where('disaster_id',$family->disaster_id)->where('status','ACTIVE')->lockForUpdate()->first();
        if ($active?->evacuation_center_id===$centerId) return $active;
        $active?->update(['status'=>'TRANSFERRED','unassigned_at'=>now(),'remarks'=>$remarks]);
        return EvacuationCenterAssignment::create(['evacuation_center_id'=>$centerId,'affected_family_id'=>$family->id,'disaster_id'=>$family->disaster_id,
            'assigned_by'=>$user->id,'assigned_at'=>$assignmentDate?\Illuminate\Support\Carbon::parse($assignmentDate):now(),'status'=>'ACTIVE','remarks'=>$remarks]);
    }

    private function validateCenter(array $data): void
    {
        if (empty($data['evacuation_center_id'])) return;
        $valid=EvacuationCenter::whereKey($data['evacuation_center_id'])->where('barangay_id',$data['barangay_id'])->where('disaster_id',$data['disaster_id'])->where('status','ACTIVE')->where('is_active',true)->exists();
        if (!$valid) throw ValidationException::withMessages(['evacuation_center_id'=>'Select an active evacuation center belonging to the selected barangay and disaster.']);
    }

    private function nextReference(string $prefix, string $model, string $column): string
    {
        $year=now()->year; $last=$model::where($column,'like',"{$prefix}-{$year}-%")->lockForUpdate()->orderByDesc($column)->value($column);
        return sprintf('%s-%d-%04d',$prefix,$year,$last?((int)substr($last,-4))+1:1);
    }
}
