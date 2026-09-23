<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class EvacuationHistoryExport implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithTitle
{
    private const NUMERIC_COLUMNS = ['capacity', 'families_recorded', 'individuals_recorded'];

    public function __construct(
        private Collection $rows,
        private array $columns,
        private array $selected,
        private string $incidentLabel,
        private string $closurePeriod,
    ) {}

    public static function columns(): array
    {
        return [
            'center' => 'Evacuation Center',
            'disaster_title' => 'Disaster Title',
            'disaster_type' => 'Disaster Type',
            'district' => 'District',
            'barangay' => 'Barangay',
            'address' => 'Complete Address',
            'capacity' => 'Capacity',
            'families_recorded' => 'Families Recorded',
            'individuals_recorded' => 'Individuals Recorded',
            'date_opened' => 'Date Opened',
            'closed_at' => 'Date Closed',
            'closed_by' => 'Closed By',
            'closure_notes' => 'Closure Note',
        ];
    }

    public function array(): array
    {
        $columnCount = count($this->selected);
        $data = [
            array_pad(['REPUBLIC OF THE PHILIPPINES'], $columnCount, ''),
            array_pad(['CITY GOVERNMENT OF TAGUIG'], $columnCount, ''),
            array_pad(['CITY SOCIAL WELFARE AND DEVELOPMENT OFFICE'], $columnCount, ''),
            array_pad(['EVACUATION CENTER HISTORY REPORT'], $columnCount, ''),
            array_pad(['AS OF '.now()->format('F d, Y, l \A\T h:i A')], $columnCount, ''),
            array_pad(['Disaster / Incident: '.strtoupper($this->incidentLabel)], $columnCount, ''),
            array_pad(['Affected Area: TAGUIG CITY'], $columnCount, ''),
            array_pad(['Closure Period: '.$this->closurePeriod], $columnCount, ''),
            array_map(fn (string $key) => $this->columns[$key], $this->selected),
        ];

        foreach ($this->rows as $row) {
            $data[] = array_map(fn (string $key) => $row[$key], $this->selected);
        }

        $data[] = array_map(function (string $key, int $index) {
            if ($index === 0) {
                return 'GRAND TOTAL';
            }

            return in_array($key, self::NUMERIC_COLUMNS, true) ? $this->rows->sum($key) : '';
        }, $this->selected, array_keys($this->selected));

        return $data;
    }

    public function title(): string
    {
        return 'Evacuation History';
    }

    public function drawings(): array
    {
        $city = new Drawing();
        $city->setName('City of Taguig')->setPath(public_path('images/city_logo.png'))->setHeight(72)->setCoordinates('A1')->setOffsetX(8)->setOffsetY(5);

        $office = new Drawing();
        $office->setName('CSWDO')->setPath(public_path('images/CSWDO.webp'))->setHeight(72)->setCoordinates(Coordinate::stringFromColumnIndex(max(1, count($this->selected) - 1)).'1')->setOffsetX(8)->setOffsetY(5);

        return [$city, $office];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();
            $lastRow = $sheet->getHighestRow();

            foreach (range(1, 8) as $row) {
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            }

            $sheet->getStyle("A1:{$lastColumn}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A1:{$lastColumn}4")->getFont()->setBold(true);
            $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setSize(14);
            foreach ([1 => 20, 2 => 20, 3 => 20, 4 => 24, 5 => 20] as $row => $height) {
                $sheet->getRowDimension($row)->setRowHeight($height);
            }

            $sheet->getStyle("A9:{$lastColumn}9")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle("A9:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('808080'));
            $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
            ]);
            $sheet->getStyle("A10:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getRowDimension(9)->setRowHeight(32);
            $sheet->freezePane('A10');
            $sheet->setAutoFilter("A9:{$lastColumn}".max(9, $lastRow - 1));
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(.35)->setRight(.25)->setBottom(.35)->setLeft(.25);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 9);
        }];
    }
}
