<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\EvacuationCenter;
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
        $family = $center->activeAssignments()->with('family')->firstOrFail()->family;
        $this->actingAs($this->staff)->getJson(route('disaster.payouts.centers.families.payout-details', [$center, $family]))
            ->assertOk()->assertJsonPath('data.affected_family.id', $family->id)
            ->assertJsonCount(3, 'data.family_members')->assertJsonPath('data.evacuation_center.id', $center->id);
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
        $this->actingAs($this->staff)->postJson(route('disaster.payouts.centers.store'), [
            'name' => 'North Test Center', 'disaster_id' => $existing->disaster_id,
            'barangay_id' => $existing->barangay_id, 'address' => '101 Test Avenue',
            'contact_number' => '09171234568', 'capacity' => 25,
            'status' => 'ACTIVE', 'payout_availability' => 'NOT_AVAILABLE',
        ])->assertCreated();
        $this->assertDatabaseHas('evacuation_centers', ['name' => 'North Test Center']);
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

    public function test_availability_button_is_visible_only_to_admin_and_superadmin(): void
    {
        $center = EvacuationCenter::where('name', 'Bagumbayan Multi-Purpose Hall')->firstOrFail();
        $this->actingAs($this->staff)->get(route('disaster.payouts.centers.show', $center))
            ->assertOk()->assertDontSee('Make Payout Available');
        foreach (['admin@gmail.com', 'superadmin@gmail.com'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())
                ->get(route('disaster.payouts.centers.show', $center))
                ->assertOk()->assertSee('Make Payout Available');
        }
    }

    private function releaseData(): array
    {
        return ['assistance_kind' => 'Emergency Cash Assistance', 'quantity' => 1, 'amount' => 10000,
            'provider' => 'City Social Welfare and Development Office', 'confirmed' => true,
            'idempotency_key' => (string) Str::uuid()];
    }
}
