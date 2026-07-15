<?php
namespace Tests\Feature\Disaster;
use App\Enums\FamilyStatus; use App\Models\Auth\User; use App\Models\Disaster\AffectedFamily; use App\Models\Disaster\WorkflowHistory; use App\Services\Disaster\DisasterAssistanceWorkflowService; use Database\Seeders\DatabaseSeeder; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Validation\ValidationException; use Tests\TestCase;
class WorkflowIntegrationTest extends TestCase {
 use RefreshDatabase; private User $admin;
 protected function setUp():void {parent::setUp();$this->seed(DatabaseSeeder::class);$this->admin=User::where('email','superadmin@gmail.com')->firstOrFail();}
 public function test_connected_module_pages_use_persisted_records():void {
  foreach(['dashboard','disaster.duplicates.index','disaster.validation.index','disaster.payroll.index','disaster.payouts.index','disaster.requirements.index','disaster.reports.index'] as $route)$this->actingAs($this->admin)->get(route($route))->assertOk();
 }
 public function test_central_service_records_every_allowed_transition_and_blocks_skips():void {
  $family=AffectedFamily::where('status',FamilyStatus::PAYROLL_READY)->firstOrFail();$service=app(DisasterAssistanceWorkflowService::class);$service->transition($family,FamilyStatus::SUBMITTED_FOR_PAYROLL,$this->admin,'payroll_submitted');$service->transition($family->refresh(),FamilyStatus::PAYOUT_PENDING,$this->admin,'payout_queued');
  $this->assertDatabaseHas('workflow_histories',['affected_family_id'=>$family->id,'from_status'=>'PAYROLL_READY','to_status'=>'SUBMITTED_FOR_PAYROLL']);$this->assertSame('PAYOUT_PENDING',$family->refresh()->status->value);
  $this->expectException(ValidationException::class);$service->transition($family->refresh(),FamilyStatus::REQUIREMENTS_COMPLETED,$this->admin,'illegal_skip');
 }
 public function test_dafac_records_are_backfilled_with_reference_and_history():void {
  $family=AffectedFamily::firstOrFail();$this->assertNotNull($family->dafacRecord->reference_number);$this->assertTrue(WorkflowHistory::where('affected_family_id',$family->id)->exists());
 }
}
