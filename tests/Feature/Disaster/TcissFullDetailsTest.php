<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\TcissMasterlistRecord;
use App\Models\Disaster\UploadedDocument;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TcissFullDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'coordinator@gmail.com')->firstOrFail();
    }

    public function test_complete_dafac_record_is_returned(): void
    {
        $record = TcissMasterlistRecord::where('source_reference', 'TCISS-2026-0001')->firstOrFail();

        $this->actingAs($this->user)->getJson(route('disaster.tciss.full-details', $record))
            ->assertOk()->assertJsonPath('data.masterlist.reference_number', 'TCISS-2026-0001')
            ->assertJsonPath('data.dafac.reference_number', 'DAFAC-2026-0001')
            ->assertJsonCount(3, 'data.family_members');
    }

    public function test_superadmin_always_receives_editable_assignment_access_without_cached_authorization(): void
    {
        $record = TcissMasterlistRecord::whereHas('affectedFamily')->firstOrFail();
        $superadmin = User::where('email', 'superadmin@gmail.com')->firstOrFail();

        $response = $this->actingAs($superadmin)
            ->getJson(route('disaster.tciss.full-details', $record))
            ->assertOk()
            ->assertJsonPath('data.assignment.can_assign', true);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('Vary', 'Cookie');
    }

    public function test_record_without_family_members_returns_an_empty_array(): void
    {
        $record = TcissMasterlistRecord::where('source_reference', 'TCISS-2026-0003')->firstOrFail();
        $this->actingAs($this->user)->getJson(route('disaster.tciss.full-details', $record))
            ->assertOk()->assertJsonCount(0, 'data.family_members');
    }

    public function test_tciss_table_shows_persistent_validation_status_instead_of_raw_workflow_status(): void
    {
        $this->actingAs($this->user)->get(route('disaster.tciss.index'))
            ->assertOk()
            ->assertSee('Verification / Validation')
            ->assertSee('Validated');
    }

    public function test_tciss_records_can_be_filtered_by_validation_status(): void
    {
        $validated = TcissMasterlistRecord::whereHas(
            'affectedFamily.validationRecords',
            fn ($query) => $query->where('status', 'Validated')
        )->firstOrFail();

        $response = $this->actingAs($this->user)->get(route('disaster.tciss.index', [
            'validation_status' => 'Validated',
        ]))->assertOk();

        $this->assertTrue($response->viewData('records')->contains('id', $validated->id));
        $this->assertTrue($response->viewData('records')->every(
            fn ($record) => $record->affectedFamily->validationRecords->contains('status', 'Validated')
        ));
    }

    public function test_attachment_uses_a_temporary_secure_url(): void
    {
        Storage::fake('local');
        $record = TcissMasterlistRecord::where('source_reference', 'TCISS-2026-0001')->firstOrFail();
        $validation = $record->affectedFamily->validationRecords()->firstOrFail();
        Storage::disk('local')->put('validation/sample.jpg', 'sample');
        UploadedDocument::create(['documentable_type' => $validation::class, 'documentable_id' => $validation->id, 'document_type' => 'validation_photo', 'file_path' => 'validation/sample.jpg', 'original_name' => 'sample.jpg', 'mime_type' => 'image/jpeg']);

        $response = $this->actingAs($this->user)->getJson(route('disaster.tciss.full-details', $record));
        $response->assertOk()->assertJsonCount(1, 'data.attachments');
        $this->assertStringContainsString('signature=', $response->json('data.attachments.0.url'));
    }

    public function test_missing_record_returns_not_found(): void
    {
        $this->actingAs($this->user)->getJson('/disaster/tciss-masterlist/999999/full-details')->assertNotFound();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $record = TcissMasterlistRecord::firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        Permission::findOrCreate('manage tciss masterlist', 'web');

        $this->actingAs($user)->getJson(route('disaster.tciss.full-details', $record))->assertForbidden();
    }
}
