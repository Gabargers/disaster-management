<?php

namespace App\Console\Commands;

use App\Models\Cms\Barangay;
use App\Models\Disaster\CswdoEvacuationCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCswdoEvacuationCenters extends Command
{
    protected $signature = 'cswdo:import-evacuation-centers {file}';
    protected $description = 'Import the official CSWDO District 1 and District 2 evacuation-center workbook';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error('Workbook not found: '.$file);
            return self::FAILURE;
        }

        $workbook = IOFactory::load($file);
        $imported = 0;

        foreach (['NEW EC D1' => 'District 1', 'NEW EC D2' => 'District 2'] as $sheetName => $district) {
            $sheet = $workbook->getSheetByName($sheetName);
            if (! $sheet) {
                $this->error("Required sheet {$sheetName} was not found.");
                return self::FAILURE;
            }

            $barangayName = $coordinator = $assistant = null;
            foreach ($sheet->toArray(null, true, true, true) as $rowNumber => $row) {
                if ($rowNumber < 5) continue;

                if ($this->clean($row['B'] ?? null) !== '') {
                    $barangayName = Str::upper($this->clean($row['B']));
                    $coordinator = $this->isName($row['E'] ?? null) ? $this->clean($row['E']) : null;
                    $assistant = $this->isName($row['F'] ?? null) ? $this->clean($row['F']) : null;
                }

                $name = $this->clean($row['C'] ?? null);
                if ($name === '' || ! $barangayName) continue;

                $barangay = Barangay::query()->whereRaw('UPPER(TRIM(name)) = ?', [$barangayName])->first();
                if (! $barangay) {
                    $barangay = Barangay::create([
                        'name' => Str::title(Str::lower($barangayName)),
                        'code' => 'CSWDO-'.Str::upper(Str::slug($barangayName)),
                        'district' => $district,
                        'is_active' => true,
                    ]);
                } elseif (! $barangay->district) {
                    $barangay->update(['district' => $district]);
                }
                CswdoEvacuationCenter::updateOrCreate(
                    ['district' => $district, 'barangay_name' => $barangayName, 'name' => $name, 'street' => $this->clean($row['D'] ?? null)],
                    ['barangay_id' => $barangay?->id, 'coordinator' => $coordinator, 'assistant_coordinator' => $assistant,
                        'capacity' => (($capacity = (int) preg_replace('/\D+/', '', (string) ($row['G'] ?? ''))) > 0) ? $capacity : null]
                );
                $imported++;
            }
        }

        $this->info("Imported {$imported} CSWDO evacuation-center entries.");
        return self::SUCCESS;
    }

    private function clean(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function isName(mixed $value): bool
    {
        return preg_match('/[A-Za-z]/', $this->clean($value)) === 1;
    }
}
