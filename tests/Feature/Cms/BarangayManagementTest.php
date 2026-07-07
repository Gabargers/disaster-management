<?php

namespace Tests\Feature\Cms;

use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BarangayManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('superadmin');
    }

    public function test_barangay_cms_page_can_be_rendered(): void
    {
        $this->actingAs($this->user)
            ->get(route('superadmin.barangay.index'))
            ->assertOk()
            ->assertSee('Barangay Management');
    }

    public function test_barangay_datatable_returns_server_side_data(): void
    {
        Barangay::query()->create([
            'name' => 'Fort Bonifacio',
            'code' => 'FB',
            'district' => 'District 2',
            'captain_name' => 'Juan Dela Cruz',
            'contact_number' => '09170000000',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('superadmin.barangay.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.name', 'Fort Bonifacio')
            ->assertJsonPath('data.0.code', 'FB');
    }

    public function test_barangay_can_be_created(): void
    {
        $this->actingAs($this->user)
            ->post(route('superadmin.barangay.store'), [
                'name' => 'Bagumbayan',
                'code' => 'bb',
                'district' => 'District 1',
                'captain_name' => 'Maria Santos',
                'contact_number' => '09171111111',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('barangays', [
            'name' => 'Bagumbayan',
            'code' => 'BB',
            'is_active' => true,
        ]);
    }

    public function test_barangay_can_be_updated(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Old Barangay',
            'code' => 'OLD',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->put(route('superadmin.barangay.update', $barangay), [
                'name' => 'New Barangay',
                'code' => 'NEW',
                'district' => 'District 3',
                'captain_name' => 'Pedro Reyes',
                'contact_number' => '09172222222',
                'is_active' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('barangays', [
            'id' => $barangay->id,
            'name' => 'New Barangay',
            'code' => 'NEW',
            'is_active' => false,
        ]);
    }

    public function test_barangay_can_be_deleted(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Barangay To Delete',
            'code' => 'DEL',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('superadmin.barangay.destroy', $barangay))
            ->assertRedirect();

        $this->assertDatabaseMissing('barangays', [
            'id' => $barangay->id,
        ]);
    }
}
