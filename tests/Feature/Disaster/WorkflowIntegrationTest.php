<?php
namespace Tests\Feature\Disaster;
use App\Enums\FamilyStatus; use App\Models\Auth\User; use App\Models\Disaster\AffectedFamily; use App\Models\Disaster\Disaster; use App\Models\Disaster\EvacuationCenter; use App\Models\Disaster\WorkflowHistory; use App\Models\Integration\PersonAffected; use App\Services\Disaster\DisasterAssistanceWorkflowService; use Database\Seeders\DatabaseSeeder; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Validation\ValidationException; use Tests\TestCase;
class WorkflowIntegrationTest extends TestCase {
 use RefreshDatabase; private User $admin;
 protected function setUp():void {parent::setUp();$this->seed(DatabaseSeeder::class);$this->admin=User::where('email','superadmin@gmail.com')->firstOrFail();}
 public function test_connected_module_pages_use_persisted_records():void {
  foreach(['dashboard','disaster.duplicates.index','disaster.validation.index','disaster.payroll.index','disaster.payouts.index','disaster.requirements.index','disaster.reports.index'] as $route)$this->actingAs($this->admin)->get(route($route))->assertOk();
  $this->actingAs($this->admin)->get(route('dashboard'))->assertViewHas('checkedQuickColumns',[]);
  $this->actingAs($this->admin)->get(route('disaster.reports.index'))->assertViewHas('checkedColumns',[]);
 }
 public function test_central_service_records_every_allowed_transition_and_blocks_skips():void {
  $family=AffectedFamily::where('status',FamilyStatus::PAYROLL_READY)->firstOrFail();$service=app(DisasterAssistanceWorkflowService::class);$service->transition($family,FamilyStatus::SUBMITTED_FOR_PAYROLL,$this->admin,'payroll_submitted');$service->transition($family->refresh(),FamilyStatus::PAYOUT_PENDING,$this->admin,'payout_queued');
  $this->assertDatabaseHas('workflow_histories',['affected_family_id'=>$family->id,'from_status'=>'PAYROLL_READY','to_status'=>'SUBMITTED_FOR_PAYROLL']);$this->assertSame('PAYOUT_PENDING',$family->refresh()->status->value);
  $this->expectException(ValidationException::class);$service->transition($family->refresh(),FamilyStatus::REQUIREMENTS_COMPLETED,$this->admin,'illegal_skip');
 }
 public function test_dafac_records_are_backfilled_with_reference_and_history():void {
  $family=AffectedFamily::firstOrFail();$this->assertNotNull($family->dafacRecord->reference_number);$this->assertTrue(WorkflowHistory::where('affected_family_id',$family->id)->exists());
 }
 public function test_monitoring_report_can_be_configured_and_exported_to_excel():void {
  $this->actingAs($this->admin)->get(route('disaster.reports.index',['columns'=>['barangay','families','individuals']]))->assertOk()->assertSee('Configure Monitoring Report')->assertSee('Evacuee Monitoring Report');
  $response=$this->actingAs($this->admin)->get(route('disaster.reports.export',['columns'=>['barangay','families','individuals']]));
  $response->assertOk()->assertHeader('content-type','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  $this->assertStringStartsWith('PK',$response->streamedContent());
 }
 public function test_dashboard_quick_view_displays_selected_age_cards():void {
  $expected=PersonAffected::distinct('control_number')->count('control_number');
  $expectedSeniors=PersonAffected::where('age','>=',60)->count();
  $this->actingAs($this->admin)->get(route('dashboard',['quick_columns'=>['total','age_60_plus']]))->assertOk()->assertSee('Configure Quick Data View')->assertSee('Evacuation Center')->assertSee('Gender')->assertSee('Age')->assertSee('Sector')->assertSee('Control Number')->assertViewHas('quickView',fn($view)=>$view['total']===$expected)->assertViewHas('quickPeople',fn($people)=>$people->total()===$expectedSeniors)->assertViewHas('selectedQuickColumns',['total','age_60_plus']);
 }
 public function test_dashboard_quick_view_filters_people_by_evacuation_center():void {
  $center=EvacuationCenter::firstOrFail();
  $expected=PersonAffected::query()->where(fn($q)=>$q->where('evacuation_center_id',$center->id)->orWhereHas('affectedFamily',fn($f)=>$f->where('evacuation_center_id',$center->id)))->distinct('control_number')->count('control_number');
  $this->actingAs($this->admin)->get(route('dashboard',['evacuation_center_id'=>$center->id]))->assertOk()->assertViewHas('quickView',fn($view)=>$view['total']===$expected);
 }
 public function test_monitoring_report_includes_linked_and_unlinked_tciss_families():void {
  $center=EvacuationCenter::whereNotNull('disaster_id')->with('barangay')->firstOrFail();
  PersonAffected::create(['control_number'=>'REPORT-UNLINKED-001','full_name'=>'Unlinked TCISS Household','age'=>42,'relationship'=>'Family Head','barangay'=>$center->barangay?->name,'city'=>'Taguig City','evacuation_center_id'=>$center->id]);
  $expected=$center->affectedFamilies()->where('disaster_id',$center->disaster_id)->count()+$center->unlinkedPersonAffecteds()->count();
  $this->actingAs($this->admin)->get(route('disaster.reports.index',['disaster_id'=>$center->disaster_id,'evacuation_center_id'=>$center->id]))->assertOk()->assertViewHas('rows',fn($rows)=>$rows->firstWhere('evacuation_center',$center->name)['families']===$expected);
 }
 public function test_center_filter_is_not_hidden_by_an_unselected_latest_incident():void {
  $center=EvacuationCenter::whereNotNull('disaster_id')->firstOrFail();
  Disaster::create(['name'=>'Newer Unselected Incident','type'=>'Typhoon','incident_date'=>now()->addDay()->format('Y-m-d'),'is_active'=>true]);
  $this->actingAs($this->admin)->get(route('disaster.reports.index',['evacuation_center_id'=>$center->id]))->assertOk()->assertViewHas('rows',fn($rows)=>$rows->contains('evacuation_center',$center->name));
 }
}
