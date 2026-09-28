<?php

namespace Database\Seeders\Disaster;

use App\Models\Cms\Barangay;
use App\Models\Disaster\CswdoEvacuationCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class CswdoEvacuationCenterCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/cswdo_evacuation_centers.psv');
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open the CSWDO evacuation-center catalog at {$path}.");
        }

        try {
            fgetcsv($handle, 0, '|');

            while (($row = fgetcsv($handle, 0, '|')) !== false) {
                if (count($row) !== 7) {
                    throw new RuntimeException('The CSWDO evacuation-center catalog contains an invalid row.');
                }

                [$district, $barangayName, $name, $street, $coordinator, $assistant, $capacity] = array_map('trim', $row);
                $displayName = Str::title(Str::lower($barangayName));
                $barangay = Barangay::firstOrCreate(
                    ['name' => $displayName],
                    [
                        'code' => 'CSWDO-'.Str::upper(Str::slug($barangayName)),
                        'district' => $district,
                        'is_active' => true,
                    ]
                );

                if (! $barangay->district) {
                    $barangay->update(['district' => $district]);
                }

                CswdoEvacuationCenter::updateOrCreate(
                    [
                        'district' => $district,
                        'barangay_name' => $barangayName,
                        'name' => $name,
                        'street' => $street,
                    ],
                    [
                        'barangay_id' => $barangay->id,
                        'coordinator' => $coordinator ?: null,
                        'assistant_coordinator' => $assistant ?: null,
                        'capacity' => $capacity !== '' ? (int) $capacity : null,
                    ]
                );
            }
        } finally {
            fclose($handle);
        }
    }
}
