<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\EvacuationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvacuationMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_pages_use_the_extracted_boundary_layer_and_database_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('evacuation-map'))
            ->assertOk()
            ->assertSee('map/oca-bbm-barangays.geojson', false)
            ->assertSee(route('evacuation-map.centers'), false);

        $this->actingAs($user)->get(route('evacuation-map.display'))
            ->assertOk()
            ->assertSee('map/oca-bbm-barangays.geojson', false)
            ->assertSee('Back to Dashboard');
    }

    public function test_center_endpoint_returns_valid_coordinates_and_ignores_invalid_or_missing_coordinates(): void
    {
        $user = User::factory()->create();
        $barangay = Barangay::create(['name' => 'Map Test Barangay', 'is_active' => true]);

        EvacuationCenter::create([
            'barangay_id' => $barangay->id,
            'name' => 'Valid Map Center',
            'address' => 'Test Street',
            'latitude' => 14.5206,
            'longitude' => 121.0509,
            'capacity' => 250,
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        EvacuationCenter::create([
            'barangay_id' => $barangay->id,
            'name' => 'Invalid Map Center',
            'latitude' => 95,
            'longitude' => 121.0509,
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        EvacuationCenter::create([
            'barangay_id' => $barangay->id,
            'name' => 'Missing Map Center',
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $this->actingAs($user)->getJson(route('evacuation-map.centers'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Valid Map Center')
            ->assertJsonPath('data.0.latitude', 14.5206)
            ->assertJsonPath('data.0.longitude', 121.0509)
            ->assertJsonPath('data.0.address', 'Test Street')
            ->assertJsonPath('data.0.barangay', 'Map Test Barangay')
            ->assertJsonPath('data.0.capacity', 250)
            ->assertJsonPath('data.0.family_count', 0)
            ->assertJsonPath('data.0.individual_count', 0);
    }
}
