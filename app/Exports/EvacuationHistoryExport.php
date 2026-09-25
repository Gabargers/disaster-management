<?php

namespace App\Exports;

use App\Models\Disaster\EvacuationCenter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class EvacuationHistoryExport implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithTitle
{
    private const HEADER_ROW = 12;

    private const LAST_COLUMN = 'R';

    public function __construct(private EvacuationCenter $center, private Collection $rows) {}

    public function array(): array
    {
        $incident = $this->center->disaster_class_name ?: ($this->center->disaster?->name ?: '—');
        $location = collect([$this->center->address, $this->center->barangay?->name, 'Taguig City'])->filter()->unique()->implode(', ');
        $opened = $this->center->date_opened?->format('F d, Y') ?: $this->center->created_at?->format('F d, Y') ?: '—';
        $closed = $this->center->closed_at?->format('F d, Y h:i A') ?: '—';
        $closedBy = $this->center->closedBy?->name ?: 'Unknown user';
        $data = [
            array_pad(['REPUBLIC OF THE PHILIPPINES'], 18, ''),
            array_pad(['CITY GOVERNMENT OF TAGUIG'], 18, ''),
            array_pad(['CITY SOCIAL WELFARE AND DEVELOPMENT OFFICE'], 18, ''),
            array_pad(['CLOSED EVACUATION CENTER FAMILY MASTERLIST'], 18, ''),
            array_pad([strtoupper($this->center->name)], 18, ''),
            array_pad(['Disaster / Incident: '.$incident], 18, ''),
            array_pad(['Location: '.($location ?: '—')], 18, ''),
            array_pad(["Opened: {$opened} | Closed: {$closed} | Closed By: {$closedBy}"], 18, ''),
            array_pad(['Closure Note: '.($this->center->closure_notes ?: '—')], 18, ''),
            array_pad(['Generated: '.now()->format('F d, Y h:i A')], 18, ''),
            array_fill(0, 18, ''),
            ['No.', 'Control Number', 'Household Head', 'Family Member', 'Relationship', 'Birthdate', 'Age', 'Sex', 'Occupation', 'Health Condition', 'Remarks', 'Complete Address', 'Barangay', 'Housing Condition', 'Ownership', 'Validation Status', 'Assigned Date', 'Household Total'],
        ];

        foreach ($this->rows->values() as $index => $row) {
            $composition = collect([$row['head']])->concat($row['members']);
            foreach ($composition as $memberIndex => $member) {
                $isHead = $memberIndex === 0;
                $data[] = [
                    $isHead ? $index + 1 : '',
                    $isHead ? ($row['control_number'] ?: '—') : '',
                    $isHead ? $row['household_head'] : '',
                    $member['name'] ?: '—',
                    $member['relationship'] ?: '—',
                    $member['birthdate'] ?: '—',
                    $member['age'] ?? '—',
                    $member['sex'] ?: '—',
                    $member['occupation'] ?: '—',
                    $member['health_condition'] ?: '—',
                    $member['remarks'] ?: '—',
                    $isHead ? ($row['address'] ?: '—') : '',
                    $isHead ? ($row['barangay'] ?: '—') : '',
                    $isHead ? ($row['housing_condition'] ?: '—') : '',
                    $isHead ? ($row['house_ownership'] ?: '—') : '',
                    $isHead ? $row['validation_status'] : '',
                    $isHead ? ($row['assigned_at']?->format('Y-m-d') ?: '—') : '',
                    $isHead ? $row['household_size'] : '',
                ];
            }
        }

        if ($this->rows->isEmpty()) {
            $data[] = array_pad(['', '', 'No assigned families recorded for this evacuation center.'], 18, '');
        }

        $total = $this->rows->sum('household_size');
        $data[] = ['', '', 'GRAND TOTAL', $total.' INDIVIDUALS', '', '', '', '', '', '', '', '', '', '', '', '', '', $total];

        return $data;
    }

    public function title(): string
    {
        return 'Family Masterlist';
    }

    public function drawings(): array
    {
        $city = new Drawing;
        $city->setName('City of Taguig')->setPath(public_path('images/city_logo.png'))->setHeight(72)->setCoordinates('A1')->setOffsetX(8)->setOffsetY(5);

        $office = new Drawing;
        $office->setName('CSWDO')->setPath(public_path('images/CSWDO.webp'))->setHeight(72)->setCoordinates('Q1')->setOffsetX(8)->setOffsetY(5);

        return [$city, $office];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $lastRow = $sheet->getHighestRow();

            foreach (range(1, 10) as $row) {
                $sheet->mergeCells("A{$row}:".self::LAST_COLUMN.$row);
            }

            $sheet->getStyle('A1:'.self::LAST_COLUMN.'5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:'.self::LAST_COLUMN.'5')->getFont()->setBold(true);
            $sheet->getStyle('A4:'.self::LAST_COLUMN.'4')->getFont()->setSize(14);
            $sheet->getStyle('A'.self::HEADER_ROW.':'.self::LAST_COLUMN.self::HEADER_ROW)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle('A'.self::HEADER_ROW.':'.self::LAST_COLUMN.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('808080'));
            for ($row = self::HEADER_ROW + 1; $row < $lastRow; $row++) {
                if ($sheet->getCell("E{$row}")->getValue() === 'Household Head') {
                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN.$row)->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF3F8']],
                    ]);
                }
            }
            $sheet->getStyle("A{$lastRow}:".self::LAST_COLUMN.$lastRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
            ]);
            $sheet->getStyle('A'.(self::HEADER_ROW + 1).':'.self::LAST_COLUMN.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(34);
            $sheet->freezePane('A'.(self::HEADER_ROW + 1));
            $sheet->setAutoFilter('A'.self::HEADER_ROW.':'.self::LAST_COLUMN.max(self::HEADER_ROW, $lastRow - 1));
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A3)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(.35)->setRight(.2)->setBottom(.35)->setLeft(.2);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, self::HEADER_ROW);
        }];
    }
}
