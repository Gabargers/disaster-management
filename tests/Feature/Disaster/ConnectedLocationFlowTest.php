<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\EvacuationCenter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectedLocationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(DatabaseSeeder::class); }

    public function test_barangay_endpoint_only_returns_its_active_centers(): void
    {
        $user=User::where('email','coordinator@gmail.com')->firstOrFail(); $barangay=Barangay::where('name','Bagumbayan')->firstOrFail();
        $response=$this->actingAs($user)->getJson(route('disaster.barangays.evacuation-centers',$barangay));
        $response->assertOk()->assertJsonFragment(['name'=>'Bagumbayan Multi-Purpose Hall']);
        foreach($response->json('data') as $center) $this->assertSame($barangay->id,EvacuationCenter::findOrFail($center['id'])->barangay_id);
    }

    public function test_reassignment_closes_history_and_updates_family_and_tciss(): void
    {
        $user=User::where('email','coordinator@gmail.com')->firstOrFail(); $barangay=Barangay::where('name','Bagumbayan')->firstOrFail();
        $family=AffectedFamily::where('barangay_id',$barangay->id)->whereHas('activeEvacuationCenterAssignment')->whereDoesntHave('payoutReleases',fn($q)=>$q->where('status','Released'))->firstOrFail();
        $new=EvacuationCenter::create(['disaster_id'=>$family->disaster_id,'barangay_id'=>$barangay->id,'name'=>'Bagumbayan Covered Court','address'=>'Bagumbayan','capacity'=>150,'status'=>'ACTIVE','payout_availability'=>'NOT_AVAILABLE','is_active'=>true]);
        $this->actingAs($user)->putJson(route('disaster.tciss.assign-evacuation-center',$family->tcissMasterlistRecord),['barangay_id'=>$barangay->id,'evacuation_center_id'=>$new->id,'assignment_date'=>now()->format('Y-m-d'),'transfer_reason'=>'Transferred due to capacity limits'])->assertOk();
        $this->assertDatabaseHas('evacuation_center_assignments',['affected_family_id'=>$family->id,'status'=>'TRANSFERRED']);
        $this->assertDatabaseHas('evacuation_center_assignments',['affected_family_id'=>$family->id,'evacuation_center_id'=>$new->id,'status'=>'ACTIVE']);
        $this->assertSame($new->id,$family->refresh()->evacuation_center_id);
        $this->assertSame($new->id,$family->tcissMasterlistRecord->evacuation_center_id);
    }

    public function test_connected_sample_seeder_is_idempotent(): void
    {
        $families=AffectedFamily::count(); $assignments=\App\Models\Disaster\EvacuationCenterAssignment::count();
        $this->seed(\Database\Seeders\Disaster\ConnectedLocationSampleSeeder::class);
        $this->assertSame($families,AffectedFamily::count()); $this->assertSame($assignments,\App\Models\Disaster\EvacuationCenterAssignment::count());
    }

    public function test_coordinator_can_transfer_from_tciss_and_other_roles_are_read_only(): void
    {
        $coordinator=User::where('email','coordinator@gmail.com')->firstOrFail();
        $payroll=User::where('email','payroll@gmail.com')->firstOrFail();
        $family=AffectedFamily::whereHas('tcissMasterlistRecord')->whereHas('activeEvacuationCenterAssignment')->firstOrFail();
        $record=$family->tcissMasterlistRecord; $current=$family->activeEvacuationCenterAssignment;
        $new=EvacuationCenter::create(['disaster_id'=>$family->disaster_id,'barangay_id'=>$family->barangay_id,'name'=>'TCISS Transfer Test Center','address'=>'Test','capacity'=>200,'status'=>'ACTIVE','payout_availability'=>'NOT_AVAILABLE','is_active'=>true]);

        $payload=['barangay_id'=>$family->barangay_id,'evacuation_center_id'=>$new->id,'assignment_date'=>now()->format('Y-m-d'),'transfer_reason'=>'TCISS transfer test'];
        $this->actingAs($payroll)->putJson(route('disaster.tciss.assign-evacuation-center',$record),$payload)->assertForbidden();
        $this->actingAs($coordinator)->putJson(route('disaster.tciss.assign-evacuation-center',$record),$payload)->assertOk();

        $this->assertDatabaseHas('evacuation_center_assignments',['id'=>$current->id,'status'=>'TRANSFERRED']);
        $this->assertDatabaseHas('evacuation_center_assignments',['affected_family_id'=>$family->id,'evacuation_center_id'=>$new->id,'status'=>'ACTIVE']);
        $this->assertDatabaseHas('workflow_histories',['affected_family_id'=>$family->id,'action'=>'evacuation_center_transferred']);
        $this->assertDatabaseHas('audit_logs',['auditable_id'=>$family->id,'action'=>'evacuation_center_reassigned']);
    }

    public function test_same_center_is_a_no_op_and_cross_barangay_is_rejected(): void
    {
        $user=User::where('email','coordinator@gmail.com')->firstOrFail();
        $family=AffectedFamily::whereHas('tcissMasterlistRecord')->whereHas('activeEvacuationCenterAssignment')->firstOrFail();
        $record=$family->tcissMasterlistRecord; $active=$family->activeEvacuationCenterAssignment; $before=$family->evacuationCenterAssignments()->count();
        $payload=['barangay_id'=>$family->barangay_id,'evacuation_center_id'=>$active->evacuation_center_id,'assignment_date'=>now()->format('Y-m-d')];
        $this->actingAs($user)->putJson(route('disaster.tciss.assign-evacuation-center',$record),$payload)->assertOk()->assertJsonPath('message','The family is already assigned to this evacuation center.');
        $this->assertSame($before,$family->evacuationCenterAssignments()->count());

        $other=EvacuationCenter::where('barangay_id','!=',$family->barangay_id)->where('disaster_id',$family->disaster_id)->first();
        if(!$other)$other=EvacuationCenter::create(['disaster_id'=>$family->disaster_id,'barangay_id'=>Barangay::whereKeyNot($family->barangay_id)->firstOrFail()->id,'name'=>'Other Barangay Center','capacity'=>100,'status'=>'ACTIVE','payout_availability'=>'NOT_AVAILABLE','is_active'=>true]);
        $this->actingAs($user)->putJson(route('disaster.tciss.assign-evacuation-center',$record),array_merge($payload,['evacuation_center_id'=>$other->id,'transfer_reason'=>'Invalid cross barangay']))->assertUnprocessable();
    }

    public function test_admin_and_superadmin_receive_editable_assignment_state(): void
    {
        $record=\App\Models\Disaster\TcissMasterlistRecord::whereHas('affectedFamily',fn($q)=>$q->whereDoesntHave('payoutReleases',fn($p)=>$p->where('status','Released')))->firstOrFail();
        foreach(['admin@gmail.com','superadmin@gmail.com'] as $email){
            $this->actingAs(User::where('email',$email)->firstOrFail())->getJson(route('disaster.tciss.full-details',$record))
                ->assertOk()->assertJsonPath('data.assignment.can_assign',true);
        }
        $viewer=User::factory()->create(['is_active'=>true]); $viewer->givePermissionTo('manage tciss masterlist');
        $this->actingAs($viewer)->getJson(route('disaster.tciss.full-details',$record))
            ->assertOk()->assertJsonPath('data.assignment.can_assign',false)
            ->assertJsonPath('data.assignment.lock_reason',null);
    }
}
