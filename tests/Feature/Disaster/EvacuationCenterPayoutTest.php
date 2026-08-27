<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\CswdoEvacuationCenter;
use App\Models\Disaster\PayoutRelease;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvacuationCenterPayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'payroll@gmail.com')->firstOrFail();
    }

    public function test_sidebar_and_page_use_evacuation_center_label(): void
    {
        $this->actingAs($this->staff)->get(route('disaster.payouts.index'))
            ->assertOk()->assertSee('Evacuation Center Management')->assertSee('Evacuation Center')
            ->assertDontSee('Payout Setup')->assertDontSee('>Assign<', false);
    }

    public function test_open_navigates_to_dedicated_center_page_with_live_totals(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertSee($center->name)->assertSee('Assigned Families')
            ->assertSee('Total Evacuees')->assertSee('Available Capacity');
    }

    public function test_assigned_family_api_is_searchable_and_calculates_household_size(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $response = $this->actingAs($this->staff)->getJson(route('disaster.payouts.centers.families', $center).'?search=Juan');
        $response->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.household_head', 'Juan Santos Dela Cruz')
            ->assertJsonPath('data.0.family_members', 3)->assertJsonPath('data.0.household_size', 4);
    }

    public function test_beneficiary_payout_details_include_family_composition(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family.familyMembers')->firstOrFail()->family;
        $this->actingAs($this->staff)->getJson(route('disaster.payouts.centers.families.payout-details', [$center, $family]))
            ->assertOk()->assertJsonPath('data.affected_family.id', $family->id)
            ->assertJsonCount(3, 'data.family_members')->assertJsonPath('data.evacuation_center.id', $center->id);
    }

    public function test_family_member_remarks_can_be_updated_from_the_center_details(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family.familyMembers')->firstOrFail()->family;
        $member = $family->familyMembers->firstOrFail();

        $this->actingAs($this->staff)->patchJson(route('disaster.payouts.centers.families.members.remarks', [$center, $family, $member]), [
            'remarks_code' => 'PWD',
        ])->assertOk()->assertJsonPath('data.remarks_code', 'PWD')->assertJsonPath('data.remarks_label', 'Person with disability');

        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'remarks_codes' => 'PWD']);
    }

    public function test_assigned_families_can_be_exported_using_the_current_filters(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family')->firstOrFail()->family;
        $response = $this->actingAs($this->staff)->get(route('disaster.payouts.centers.families.export', [$center, 'search'=>$family->household_head_given_name]));

        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
        $path = tempnam(sys_get_temp_dir(), 'center-export-');
        file_put_contents($path, $content);
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        unlink($path);
        $workbookText = collect($sheet->toArray())->flatten()->implode(' ');
        $this->assertStringContainsString($family->household_head_full_name, $workbookText);
        $this->assertStringContainsString($family->familyMembers->firstOrFail()->name, $workbookText);
    }

    public function test_only_admin_and_superadmin_can_transfer_a_family_from_the_center_page(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $assignment = $center->activeAssignments()->whereDoesntHave('family.payoutReleases', fn ($query) => $query->where('status', 'Released'))->with('family')->firstOrFail();
        $target = EvacuationCenter::create(['uuid'=>(string)Str::uuid(),'disaster_id'=>$center->disaster_id,'barangay_id'=>$center->barangay_id,'district'=>$center->district,'name'=>'Transfer Test Center','capacity'=>100,'status'=>'ACTIVE','is_active'=>true]);

        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))->assertOk()->assertViewHas('canTransferFamilies', false);
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($admin)->get(route('disaster.payouts.centers.show', $center))->assertOk()->assertViewHas('canTransferFamilies', true)->assertSee('Transfer Evacuation Center');
        $this->actingAs($admin)->patchJson(route('disaster.payouts.centers.families.transfer', [$center, $assignment->family]), ['evacuation_center_id'=>$target->id,'reason'=>'Transferred for capacity balancing.'])->assertOk();

        $this->assertDatabaseHas('affected_families', ['id'=>$assignment->affected_family_id,'evacuation_center_id'=>$target->id]);
        $this->assertDatabaseHas('evacuation_center_assignments', ['id'=>$assignment->id,'status'=>'TRANSFERRED']);
    }

    public function test_bagumbayan_center_returns_its_five_connected_sample_families(): void
    {
        $center = EvacuationCenter::where('name', 'Bagumbayan Multi-Purpose Hall')->firstOrFail();
        $this->actingAs($this->staff)->getJson(route('disaster.payouts.centers.families', $center))
            ->assertOk()->assertJsonPath('meta.total', 5)->assertJsonCount(5, 'data');
    }

    public function test_authorized_user_can_create_a_center(): void
    {
        $existing = EvacuationCenter::firstOrFail();
        $catalog = CswdoEvacuationCenter::create([
            'district' => 'District 1', 'barangay_id' => $existing->barangay_id,
            'barangay_name' => $existing->barangay->name, 'name' => 'North Test Center',
            'street' => '101 Test Avenue', 'coordinator' => 'CSWD Coordinator',
            'assistant_coordinator' => 'CSWD Assistant Coordinator', 'capacity' => 25,
        ]);
        $this->actingAs($this->staff)->postJson(route('disaster.payouts.centers.store'), [
            'cswdo_catalog_id' => $catalog->id, 'disaster_id' => $existing->disaster_id,
        ])->assertCreated();
        $this->assertDatabaseHas('evacuation_centers', [
            'name' => 'North Test Center', 'address' => '101 Test Avenue',
            'contact_person' => 'CSWD Coordinator',
            'assistant_coordinator' => 'CSWD Assistant Coordinator', 'capacity' => 25,
        ]);
    }

    public function test_release_requires_a_photo(): void
    {
        $release = PayoutRelease::where('status', 'Scheduled')->orderBy('id')->firstOrFail();
        $this->actingAs($this->staff)->postJson(route('disaster.payouts.releases.release', $release), $this->releaseData())
            ->assertUnprocessable()->assertJsonPath('message', 'A beneficiary payout photo is required.');
    }

    public function test_release_succeeds_and_duplicate_release_is_blocked(): void
    {
        Storage::fake('local');
        $release = PayoutRelease::where('status', 'Scheduled')->orderBy('id')->firstOrFail();
        $data = $this->releaseData() + ['photo' => UploadedFile::fake()->image('beneficiary.jpg', 640, 480)];
        $this->actingAs($this->staff)->post(route('disaster.payouts.releases.release', $release), $data)
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('payout_releases', ['id' => $release->id, 'status' => 'Released']);
        $this->assertDatabaseHas('payout_releases', ['id' => $release->id, 'payout_photo_original_name' => 'beneficiary.jpg', 'payout_photo_mime_type' => 'image/jpeg']);
        $this->actingAs($this->staff)->postJson(route('disaster.payouts.releases.release', $release), $this->releaseData())
            ->assertConflict()->assertJsonPath('message', 'This payout has already been released.');
    }

    public function test_new_release_is_immediately_reflected_in_dashboard_and_released_payout_list(): void
    {
        Storage::fake('local');
        $release = PayoutRelease::where('status', 'Scheduled')->orderBy('id')->firstOrFail();
        $before = PayoutRelease::where('status', 'Released')->count();

        $this->actingAs($this->staff)
            ->post(route('disaster.payouts.releases.release', $release), $this->releaseData() + [
                'photo' => UploadedFile::fake()->image('dashboard-proof.jpg', 640, 480),
            ])
            ->assertOk();

        $dashboard = $this->actingAs($this->staff)->get(route('dashboard'))->assertOk();
        $this->assertSame($before + 1, $dashboard->viewData('metrics')['RELEASED_PAYOUTS']);
        $this->assertStringContainsString('no-store', (string) $dashboard->headers->get('Cache-Control'));

        $this->actingAs($this->staff)->get(route('disaster.payroll.index'))
            ->assertOk()
            ->assertViewHas('families', fn ($families) => $families->contains('id', $release->affected_family_id));
    }

    public function test_unauthorized_user_cannot_release(): void
    {
        $release = PayoutRelease::firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->postJson(route('disaster.payouts.releases.release', $release), $this->releaseData())->assertForbidden();
    }

    public function test_only_admin_or_superadmin_can_manage_payout_availability(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $center->update(['payout_availability' => 'NOT_AVAILABLE']);

        $this->actingAs($this->staff)->postJson(route('disaster.payouts.centers.availability', $center), [
            'payout_availability' => 'NOT_AVAILABLE',
        ])->assertForbidden();

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($admin)->postJson(route('disaster.payouts.centers.availability', $center), [
            'payout_availability' => 'NOT_AVAILABLE',
        ])->assertOk();

        $superadmin = User::where('email', 'superadmin@gmail.com')->firstOrFail();
        $this->actingAs($superadmin)->postJson(route('disaster.payouts.centers.availability', $center), [
            'payout_availability' => 'NOT_AVAILABLE',
        ])->assertOk();
    }

    public function test_availability_button_is_removed_for_all_roles(): void
    {
        $center = EvacuationCenter::where('name', 'Bagumbayan Multi-Purpose Hall')->firstOrFail();
        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertDontSee('Make Payout Available');
        foreach (['admin@gmail.com', 'superadmin@gmail.com'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())
                ->get(route('disaster.payouts.centers.show', $center))
                ->assertOk()->assertDontSee('Make Payout Available');
        }
    }

    public function test_bfp_certificate_is_uploaded_per_evacuation_center(): void
    {
        Storage::fake('local');
        $center = EvacuationCenter::firstOrFail();
        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertSee('BFP Certificate')->assertSee('Export Excel');

        $this->actingAs($this->staff)->post(route('disaster.payouts.centers.bfp-certificate', $center), [
            'bfp_certificate' => UploadedFile::fake()->create('center-bfp.pdf', 128, 'application/pdf'),
        ])->assertRedirect();

        $document = $center->documents()->where('document_type', 'bfp_certificate')->firstOrFail();
        $this->assertSame('center-bfp.pdf', $document->original_name);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_payroll_modal_uses_household_head_valid_id_requirement(): void
    {
        Storage::fake('local');
        $release = PayoutRelease::where('status', 'Released')->firstOrFail();
        $family = $release->affectedFamily;

        $this->actingAs($this->staff)->get(route('disaster.payroll.index'))
            ->assertOk()->assertSee('Valid ID of Household Head')->assertDontSee('Bureau of Fire Protection (BFP) Certificate');
        $this->actingAs($this->staff)->post(route('disaster.payroll.requirements', $family), [
            'valid_id_document' => UploadedFile::fake()->image('household-head-id.jpg'),
        ])->assertOk()->assertJsonPath('data.valid_id_name', 'household-head-id.jpg');

        $this->assertDatabaseHas('uploaded_documents', [
            'documentable_type' => \App\Models\Disaster\PostPayoutRequirement::class,
            'document_type' => 'valid_id_document', 'original_name' => 'household-head-id.jpg',
        ]);
    }

    private function releaseData(): array
    {
        return ['assistance_kind' => 'Emergency Cash Assistance', 'quantity' => 1, 'amount' => 10000,
            'provider' => 'City Social Welfare and Development Office', 'confirmed' => true,
            'idempotency_key' => (string) Str::uuid()];
    }
}
