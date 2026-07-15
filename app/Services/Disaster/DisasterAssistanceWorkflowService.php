<?php
namespace App\Services\Disaster;
use App\Enums\FamilyStatus; use App\Models\Auth\User; use App\Models\Disaster\AffectedFamily; use App\Models\Disaster\AuditLog; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class DisasterAssistanceWorkflowService {
 public function transition(AffectedFamily $family,FamilyStatus $to,User $user,string $action,?string $remarks=null,array $metadata=[]):AffectedFamily {
  return DB::transaction(function()use($family,$to,$user,$action,$remarks,$metadata){$family=AffectedFamily::lockForUpdate()->findOrFail($family->id);$from=$family->status instanceof FamilyStatus?$family->status:FamilyStatus::from($family->status);if(!$from->canTransitionTo($to))throw ValidationException::withMessages(['status'=>"Cannot move from {$from->value} to {$to->value}."]);$family->update(['status'=>$to,'updated_by'=>$user->id]);$family->workflowHistories()->create(['from_status'=>$from->value,'to_status'=>$to->value,'action'=>$action,'remarks'=>$remarks,'performed_by'=>$user->id,'performed_at'=>now(),'metadata'=>$metadata]);AuditLog::create(['user_id'=>$user->id,'auditable_type'=>$family::class,'auditable_id'=>$family->id,'action'=>$action,'old_values'=>['status'=>$from->value],'new_values'=>['status'=>$to->value]+$metadata]);return $family->refresh();});
 }
}
