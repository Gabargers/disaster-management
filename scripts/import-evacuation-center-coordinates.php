<?php

declare(strict_types=1);

use App\Models\Cms\Barangay;
use App\Models\Disaster\EvacuationCenter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
$overwriteCoordinates = in_array('--overwrite-coordinates', $argv, true);

if (!$source || !is_file($source)) {
    fwrite(STDERR, "Usage: php scripts/import-evacuation-center-coordinates.php <file.xlsx> [--dry-run] [--overwrite-coordinates]\n");
    exit(1);
}

$normalize = static fn (?string $value): string => mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', (string) $value)));
$barangays = Barangay::all()->keyBy(fn (Barangay $barangay) => $normalize($barangay->name));
$workbook = IOFactory::load($source);
$rows = [];
$unmatched = [];

foreach (['NEW EC D1', 'NEW EC D2'] as $sheetName) {
    $sheet = $workbook->getSheetByName($sheetName);
    if (!$sheet) {
        fwrite(STDERR, "Required sheet not found: {$sheetName}\n");
        exit(1);
    }

    $currentBarangay = '';
    foreach ($sheet->toArray(null, true, true, true) as $rowNumber => $row) {
        if ($rowNumber < 5) continue;
        if (trim((string) ($row['B'] ?? '')) !== '') {
            $currentBarangay = trim((string) $row['B']);
        }

        $name = trim((string) preg_replace('/\s+/u', ' ', (string) ($row['C'] ?? '')));
        $latitude = filter_var($row['H'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($row['I'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($name === '' || $latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            continue;
        }

        $barangay = $barangays->get($normalize($currentBarangay));
        if (!$barangay) {
            $unmatched[] = "{$sheetName} row {$rowNumber}: {$currentBarangay}";
            continue;
        }

        preg_match('/\d+/', (string) ($row['G'] ?? ''), $capacityMatch);
        $rows[] = [
            'barangay' => $barangay,
            'name' => $name,
            'address' => trim((string) ($row['D'] ?? '')) ?: null,
            'capacity' => isset($capacityMatch[0]) ? (int) $capacityMatch[0] : null,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }
}

if ($unmatched !== []) {
    fwrite(STDERR, "Unmatched barangays:\n - ".implode("\n - ", array_unique($unmatched))."\n");
    exit(1);
}

$created = 0;
$updated = 0;
$operation = function () use ($rows, $overwriteCoordinates, &$created, &$updated): void {
    foreach ($rows as $row) {
        $center = EvacuationCenter::firstOrNew([
            'barangay_id' => $row['barangay']->id,
            'name' => $row['name'],
        ]);
        $exists = $center->exists;
        $values = [
            'district' => $row['barangay']->district,
            'address' => $row['address'],
            'capacity' => $row['capacity'],
            'status' => 'ACTIVE',
            'is_active' => true,
        ];
        if (!$exists || $overwriteCoordinates || $center->latitude === null || $center->longitude === null) {
            $values['latitude'] = $row['latitude'];
            $values['longitude'] = $row['longitude'];
        }
        $center->fill($values)->save();
        $exists ? $updated++ : $created++;
    }
};

if ($dryRun) {
    DB::beginTransaction();
    try {
        $operation();
        DB::rollBack();
    } catch (Throwable $exception) {
        DB::rollBack();
        throw $exception;
    }
} else {
    DB::transaction($operation);
}

echo sprintf(
    "%s %d rows: %d create, %d update. Existing manual coordinates %s.\n",
    $dryRun ? 'Validated' : 'Imported',
    count($rows),
    $created,
    $updated,
    $overwriteCoordinates ? 'overwritten' : 'preserved'
);
