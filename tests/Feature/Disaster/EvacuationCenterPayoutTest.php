<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\CswdoEvacuationCenter;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\PayoutRelease;
use App\Models\Disaster\PostPayoutRequirement;
use App\Models\Integration\PersonAffected;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EvacuationCenterPayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'paymaster@gmail.com')->firstOrFail();
    }

    public function test_sidebar_and_page_use_evacuation_center_label(): void
    {
        $this->actingAs($this->staff)->get(route('disaster.payouts.index'))
            ->assertOk()->assertSee('Evacuation Centers')->assertSee('Evacuation Center')
            ->assertDontSee('Evacuation History')->assertDontSee('Close Center')
            ->assertDontSee('Payout Setup')->assertDontSee('>Assign<', false);
    }

    public function test_official_center_catalog_and_barangays_are_available_without_a_manual_import(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertDatabaseHas('barangays', ['name' => 'Bagumbayan', 'district' => 'District 1']);
        $this->assertDatabaseHas('cswdo_evacuation_center_catalog', [
            'barangay_name' => 'BAGUMBAYAN',
            'name' => 'COMMUNITY MULTI PURPOSE EVACUATION CENTER',
            'capacity' => 50,
        ]);

        $this->actingAs($admin)->get(route('disaster.payouts.index'))
            ->assertOk()
            ->assertViewHas('centerCatalogData', fn ($catalog) => $catalog->count() >= 60)
            ->assertSee('COMMUNITY MULTI PURPOSE EVACUATION CENTER');
    }

    public function test_center_can_be_closed_and_is_moved_to_evacuation_history(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($admin)->patchJson(route('disaster.payouts.centers.close', $center), [
            'closure_notes' => 'All families have returned home safely.',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('evacuation_centers', [
            'id' => $center->id, 'status' => 'CLOSED', 'is_active' => false,
            'closed_by' => $admin->id, 'closure_notes' => 'All families have returned home safely.',
        ]);
        $this->actingAs($this->staff)->get(route('disaster.payouts.index'))->assertOk()->assertDontSee($center->name);
        $this->actingAs($admin)->get(route('disaster.payouts.history'))->assertOk()
            ->assertSee($center->name)->assertSee('Select a closed evacuation center')
            ->assertDontSee('Juan Santos Dela Cruz');
        $this->actingAs($admin)->get(route('disaster.payouts.history', ['evacuation_center_id' => $center->id]))
            ->assertOk()->assertSee('All families have returned home safely.')
            ->assertSee('Juan Santos Dela Cruz')->assertSee('Ana Dela Cruz')
            ->assertSee('Family Composition')->assertSee('Export Masterlist');
        $export = $this->actingAs($admin)->get(route('disaster.payouts.history.export', [
            'evacuation_center_id' => $center->id,
        ]));
        $export->assertOk()->assertDownload();
        $workbook = $export->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'history-masterlist-');
        file_put_contents($path, $workbook);
        $spreadsheet = IOFactory::load($path);
        unlink($path);
        $this->assertCount(2, $spreadsheet->getAllSheets());
        $this->assertSame('Family Masterlist', $spreadsheet->getSheet(0)->getTitle());
        $masterlistText = collect($spreadsheet->getSheet(0)->toArray())->flatten()->implode(' ');
        $this->assertStringContainsString('Juan Santos Dela Cruz', $masterlistText);
        $this->assertStringContainsString('Ana Dela Cruz', $masterlistText);
        $this->assertStringContainsString('Household Head', $masterlistText);
        $this->assertStringContainsString('xl/worksheets/sheet2.xml', $workbook);
        $this->assertStringContainsString('xl/media/', $workbook);
        $this->actingAs($this->staff)->getJson(route('evacuation-map.centers'))->assertOk()
            ->assertJsonMissing(['id' => $center->id]);
    }

    public function test_closing_a_center_requires_a_closure_note(): void
    {
        $center = EvacuationCenter::firstOrFail();
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($admin)->patchJson(route('disaster.payouts.centers.close', $center), [])
            ->assertUnprocessable()->assertJsonValidationErrors('closure_notes');
    }

    public function test_non_admin_cannot_close_centers_or_view_evacuation_history(): void
    {
        $center = EvacuationCenter::firstOrFail();

        $this->actingAs($this->staff)->get(route('disaster.payouts.history'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('disaster.payouts.history.export'))->assertForbidden();
        $this->actingAs($this->staff)->patchJson(route('disaster.payouts.centers.close', $center), [
            'closure_notes' => 'Should not be accepted.',
        ])->assertForbidden();
        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertDontSee('Close Center');
    }

    public function test_history_export_requires_a_selected_closed_center(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $activeCenter = EvacuationCenter::where('status', 'ACTIVE')->firstOrFail();

        $this->actingAs($admin)->get(route('disaster.payouts.history.export'))
            ->assertSessionHasErrors('evacuation_center_id');
        $this->actingAs($admin)->get(route('disaster.payouts.history.export', [
            'evacuation_center_id' => $activeCenter->id,
        ]))->assertSessionHasErrors('evacuation_center_id');
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
            ->assertJsonPath('data.affected_family.house_ownership', $family->house_ownership)
            ->assertJsonPath('data.affected_family.health_condition', $family->health_condition)
            ->assertJsonPath('data.affected_family.housing_condition', $family->housing_condition)
            ->assertJsonPath('data.payout.can_release', true)
            ->assertJsonCount(3, 'data.family_members')->assertJsonPath('data.evacuation_center.id', $center->id)
            ->assertJsonPath('data.payout.released_by', $this->staff->name);

        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertSee('House Ownership')->assertSee('Health Condition')->assertSee('Housing Condition');
    }

    public function test_family_member_remarks_can_be_updated_from_the_center_details(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family.familyMembers')->firstOrFail()->family;
        $member = $family->familyMembers->firstOrFail();

        $this->actingAs($admin)->patchJson(route('disaster.payouts.centers.families.members.remarks', [$center, $family, $member]), [
            'remarks_code' => 'PWD',
        ])->assertOk()->assertJsonPath('data.remarks_code', 'PWD')->assertJsonPath('data.remarks_label', 'Person with disability');

        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'remarks_codes' => 'PWD']);
    }

    public function test_encoder_can_validate_household_conditions_and_the_form_locks_after_validation(): void
    {
        $encoder = User::where('email', 'encoder@gmail.com')->firstOrFail();
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family')->firstOrFail()->family;
        $family->validationRecords()->delete();

        $payload = [
            'house_ownership' => 'Owner',
            'health_condition' => 'N/A',
            'housing_condition' => 'Partially Damaged',
        ];

        $this->actingAs($encoder)
            ->patchJson(route('disaster.payouts.centers.families.housing-condition', [$center, $family]), $payload)
            ->assertOk()
            ->assertJsonPath('data.health_condition', 'N/A')
            ->assertJsonPath('data.validation_status', 'Validated');

        $this->assertDatabaseHas('affected_families', ['id' => $family->id, 'health_condition' => 'N/A']);
        $this->actingAs($encoder)
            ->patchJson(route('disaster.payouts.centers.families.housing-condition', [$center, $family]), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('validation');

        $this->actingAs($this->staff)
            ->patchJson(route('disaster.payouts.centers.families.housing-condition', [$center, $family]), $payload)
            ->assertForbidden();

        $this->actingAs($encoder)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()
            ->assertViewHas('canProcessPayouts', false)
            ->assertViewHas('canManageHouseholdConditions', true)
            ->assertSee('<option value="N/A">N/A</option>', false)
            ->assertSee('button.disabled=!canManageHouseholdConditions||validated', false);

        $this->actingAs($encoder)
            ->getJson(route('disaster.payouts.centers.families.payout-details', [$center, $family]))
            ->assertOk()
            ->assertJsonPath('data.affected_family.validation_status', 'Validated')
            ->assertJsonPath('data.payout', null)
            ->assertJsonCount(0, 'data.payout_history');
    }

    public function test_encoder_can_complete_conditions_for_an_unlinked_tciss_family(): void
    {
        $encoder = User::where('email', 'encoder@gmail.com')->firstOrFail();
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = PersonAffected::create([
            'control_number' => 'PAYROLL-CONDITION-001',
            'full_name' => 'Payroll Condition Family',
            'family_head_control_number' => 'PAYROLL-CONDITION-001',
            'relationship' => 'Family Head',
            'housing' => 'Owner',
            'barangay' => $center->barangay?->name,
            'evacuation_center_id' => $center->id,
            'evacuation_center_assigned_by' => $encoder->id,
            'evacuation_center_assigned_at' => now(),
        ]);

        $this->actingAs($encoder)
            ->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()
            ->assertViewHas('canManageHouseholdConditions', true);

        $this->actingAs($encoder)
            ->patchJson(route('disaster.payouts.centers.tciss-families.conditions', [$center, $family]), [
                'health_condition' => 'N/A',
                'housing_condition' => 'Partially Damaged',
            ])
            ->assertOk()
            ->assertJsonPath('data.house_ownership', 'Owner')
            ->assertJsonPath('data.health_condition', 'N/A')
            ->assertJsonPath('data.housing_condition', 'Partially Damaged')
            ->assertJsonPath('data.validation_status', 'Validated');

        $this->assertNotNull($family->refresh()->affected_family_id);
    }

    public function test_payroll_has_payout_only_access_and_must_wait_for_encoder_validation(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family')->firstOrFail()->family;
        $family->validationRecords()->delete();

        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()
            ->assertViewHas('payoutOnlyMode', true)
            ->assertViewHas('canProcessPayouts', true)
            ->assertViewHas('canManageHouseholdConditions', false)
            ->assertSee('Awaiting Validation')
            ->assertSee("payoutOnlyMode?'Payout'", false)
            ->assertSee("syncModalActions('payout-tab')", false);

        $this->actingAs($this->staff)
            ->getJson(route('disaster.payouts.centers.families.payout-details', [$center, $family]))
            ->assertForbidden()
            ->assertJsonPath('message', 'This family must be validated by an encoder before Payroll can process its payout.');
    }

    public function test_assigned_families_can_be_exported_using_the_current_filters(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $family = $center->activeAssignments()->with('family')->firstOrFail()->family;
        $response = $this->actingAs($this->staff)->get(route('disaster.payouts.centers.families.export', [$center, 'search' => $family->household_head_given_name]));

        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
        $path = tempnam(sys_get_temp_dir(), 'center-export-');
        file_put_contents($path, $content);
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);
        $workbookText = collect($sheet->toArray())->flatten()->implode(' ');
        $this->assertStringContainsString($family->household_head_full_name, $workbookText);
        $this->assertStringContainsString($family->familyMembers->firstOrFail()->name, $workbookText);
    }

    public function test_only_admin_and_superadmin_can_transfer_a_family_from_the_center_page(): void
    {
        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $assignment = $center->activeAssignments()->whereDoesntHave('family.payoutReleases', fn ($query) => $query->where('status', 'Released'))->with('family')->firstOrFail();
        $target = EvacuationCenter::create(['uuid' => (string) Str::uuid(), 'disaster_id' => $center->disaster_id, 'barangay_id' => $center->barangay_id, 'district' => $center->district, 'name' => 'Transfer Test Center', 'capacity' => 100, 'status' => 'ACTIVE', 'is_active' => true]);

        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))->assertOk()->assertViewHas('canTransferFamilies', false);
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($admin)->get(route('disaster.payouts.centers.show', $center))->assertOk()->assertViewHas('canTransferFamilies', true)->assertSee('Transfer Evacuation Center');
        $this->actingAs($admin)->patchJson(route('disaster.payouts.centers.families.transfer', [$center, $assignment->family]), ['evacuation_center_id' => $target->id, 'reason' => 'Transferred for capacity balancing.'])->assertOk();

        $this->assertDatabaseHas('affected_families', ['id' => $assignment->affected_family_id, 'evacuation_center_id' => $target->id]);
        $this->assertDatabaseHas('evacuation_center_assignments', ['id' => $assignment->id, 'status' => 'TRANSFERRED']);
    }

    public function test_bagumbayan_center_returns_its_five_connected_sample_families(): void
    {
        $center = EvacuationCenter::where('name', 'Bagumbayan Multi-Purpose Hall')->firstOrFail();
        $this->actingAs($this->staff)->getJson(route('disaster.payouts.centers.families', $center))
            ->assertOk()->assertJsonPath('meta.total', 5)->assertJsonCount(5, 'data');
    }

    public function test_authorized_user_can_create_a_center(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $existing = EvacuationCenter::firstOrFail();
        $catalog = CswdoEvacuationCenter::create([
            'district' => 'District 1', 'barangay_id' => $existing->barangay_id,
            'barangay_name' => $existing->barangay->name, 'name' => 'North Test Center',
            'street' => '101 Test Avenue', 'coordinator' => 'CSWD Coordinator',
            'assistant_coordinator' => 'CSWD Assistant Coordinator', 'capacity' => 25,
        ]);
        EvacuationCenter::create([
            'name' => 'North Test Coordinate Reference', 'barangay_id' => $existing->barangay_id,
            'address' => '101 Test Avenue', 'capacity' => 25, 'status' => 'ACTIVE',
            'is_active' => true, 'latitude' => 14.521234, 'longitude' => 121.051234,
        ]);
        $this->actingAs($admin)->postJson(route('disaster.payouts.centers.store'), [
            'cswdo_catalog_id' => $catalog->id, 'disaster_type' => 'Typhoon',
            'date_opened' => '2026-09-20', 'disaster_title' => 'Typhoon Enteng',
        ])->assertCreated();
        $this->assertDatabaseHas('evacuation_centers', [
            'name' => 'North Test Center', 'address' => '101 Test Avenue',
            'contact_person' => 'CSWD Coordinator',
            'assistant_coordinator' => 'CSWD Assistant Coordinator', 'capacity' => 25,
            'disaster_class_name' => 'Typhoon Enteng',
            'latitude' => 14.521234, 'longitude' => 121.051234,
        ]);
        $created = EvacuationCenter::where('name', 'North Test Center')->with('disaster')->firstOrFail();
        $this->assertSame('2026-09-20', $created->date_opened->toDateString());
        $this->assertSame('Typhoon Enteng', $created->disaster->name);
        $this->assertSame('Typhoon', $created->disaster->type);

        $this->actingAs($admin)->postJson(route('disaster.payouts.centers.store'), [
            'cswdo_catalog_id' => $catalog->id, 'disaster_type' => 'Fire',
            'date_opened' => '2026-09-20', 'disaster_title' => 'Fire Incident 2026',
        ])->assertCreated();
        $this->assertSame(2, EvacuationCenter::where('name', 'North Test Center')->count());

        $this->actingAs($admin)->postJson(route('disaster.payouts.centers.store'), [
            'cswdo_catalog_id' => $catalog->id, 'disaster_type' => 'Fire',
            'date_opened' => '2026-09-20', 'disaster_title' => 'Fire Incident 2026',
        ])->assertUnprocessable()->assertJsonValidationErrors('cswdo_catalog_id');
    }

    public function test_admin_can_supply_capacity_when_the_official_catalog_has_none(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $catalog = CswdoEvacuationCenter::where('name', 'BARANGAY HALL (3RD FLOOR)')
            ->where('barangay_name', 'PEMBO')->firstOrFail();
        $payload = [
            'cswdo_catalog_id' => $catalog->id,
            'disaster_type' => 'Fire',
            'date_opened' => '2026-09-20',
            'disaster_title' => 'Pembo Fire Incident',
        ];

        $this->actingAs($admin)->postJson(route('disaster.payouts.centers.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('capacity');

        $this->actingAs($admin)->postJson(route('disaster.payouts.centers.store'), $payload + ['capacity' => 75])
            ->assertCreated();
        $this->assertDatabaseHas('evacuation_centers', [
            'cswdo_catalog_id' => $catalog->id,
            'name' => 'BARANGAY HALL (3RD FLOOR)',
            'capacity' => 75,
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
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $data = $this->releaseData() + [
            'photo' => UploadedFile::fake()->image('beneficiary.jpg', 640, 480),
            'released_by' => $admin->id,
        ];
        $this->actingAs($this->staff)->post(route('disaster.payouts.releases.release', $release), $data)
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('data.released_by', $this->staff->name);
        $this->assertDatabaseHas('payout_releases', [
            'id' => $release->id, 'status' => 'Released', 'released_by' => $this->staff->id,
        ]);
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

    public function test_payout_availability_action_is_removed_for_every_role(): void
    {
        $center = EvacuationCenter::where('name', 'Bagumbayan Multi-Purpose Hall')->firstOrFail();
        foreach (['paymaster@gmail.com', 'admin@gmail.com', 'superadmin@gmail.com'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())
                ->get(route('disaster.payouts.centers.show', $center))
                ->assertOk()
                ->assertDontSee('Make Payout Available')
                ->assertDontSee('Disable Payout');
        }

        $this->assertFalse(Route::has('disaster.payouts.centers.availability'));
        $encoder = User::where('email', 'encoder@gmail.com')->firstOrFail();
        $this->assertTrue($encoder->can('manage household conditions'));
        $this->assertTrue($encoder->can('view evacuation centers'));
        $this->assertFalse($this->staff->can('manage household conditions'));
        $this->assertFalse($this->staff->can('manage payout availability'));
    }

    public function test_bfp_certificate_is_uploaded_per_evacuation_center(): void
    {
        Storage::fake('local');
        $center = EvacuationCenter::firstOrFail();
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertDontSee('BFP Certificate')->assertSee('Export Excel');
        $this->actingAs($admin)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertSee('BFP Certificate')->assertSee('Export Excel');

        $this->actingAs($admin)->post(route('disaster.payouts.centers.bfp-certificate', $center), [
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
            'documentable_type' => PostPayoutRequirement::class,
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
