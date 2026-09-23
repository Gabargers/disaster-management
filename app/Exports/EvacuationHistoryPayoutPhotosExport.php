<?php

namespace App\Exports;

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

class EvacuationHistoryPayoutPhotosExport implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithTitle
{
    private const HEADER_ROW = 8;

    public function __construct(private Collection $rows, private string $incidentLabel, private string $closurePeriod) {}

    public function array(): array
    {
        $data = [
            array_pad(['REPUBLIC OF THE PHILIPPINES'], 11, ''),
            array_pad(['CITY GOVERNMENT OF TAGUIG'], 11, ''),
            array_pad(['CITY SOCIAL WELFARE AND DEVELOPMENT OFFICE'], 11, ''),
            array_pad(['EVACUATION HISTORY — BENEFICIARY PAYOUT PHOTOS'], 11, ''),
            array_pad(['AS OF '.now()->format('F d, Y, l \A\T h:i A')], 11, ''),
            array_pad(['Disaster / Incident: '.strtoupper($this->incidentLabel)], 11, ''),
            array_pad(['Closure Period: '.$this->closurePeriod], 11, ''),
            ['No.', 'Evacuation Center', 'Disaster Title', 'Household Head', 'DAFAC Reference', 'Assistance', 'Amount', 'Released', 'Released By', 'Payout Photo', 'Caption'],
        ];

        foreach ($this->rows->values() as $index => $row) {
            $data[] = [
                $index + 1,
                $row['center'],
                $row['disaster_title'],
                $row['household'],
                $row['dafac_reference'],
                $row['assistance'],
                $row['amount'],
                $row['released_at'],
                $row['released_by'],
                $row['photo_path'] ? '' : 'Image unavailable',
                $row['caption'],
            ];
        }

        if ($this->rows->isEmpty()) {
            $data[] = ['', 'No released payout photos found for the selected closed evacuation centers.'];
        }

        return $data;
    }

    public function title(): string
    {
        return 'Payout Photos';
    }

    public function drawings(): array
    {
        $drawings = [];
        $city = new Drawing();
        $city->setName('City of Taguig')->setPath(public_path('images/city_logo.png'))->setHeight(72)->setCoordinates('A1')->setOffsetX(8)->setOffsetY(5);
        $drawings[] = $city;

        $office = new Drawing();
        $office->setName('CSWDO')->setPath(public_path('images/CSWDO.webp'))->setHeight(72)->setCoordinates('J1')->setOffsetX(8)->setOffsetY(5);
        $drawings[] = $office;

        foreach ($this->rows->values() as $index => $row) {
            if (! $row['photo_path']) {
                continue;
            }

            $photo = new Drawing();
            $photo->setName('Payout Proof '.($index + 1))
                ->setDescription($row['household'])
                ->setPath($row['photo_path'])
                ->setHeight(105)
                ->setCoordinates('J'.(self::HEADER_ROW + 1 + $index))
                ->setOffsetX(7)
                ->setOffsetY(5);
            $drawings[] = $photo;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $lastRow = $sheet->getHighestRow();
            foreach (range(1, 7) as $row) {
                $sheet->mergeCells("A{$row}:K{$row}");
            }
            $sheet->getStyle('A1:K5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:K4')->getFont()->setBold(true);
            $sheet->getStyle('A4:K4')->getFont()->setSize(14);
            $sheet->getStyle('A8:K8')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle("A8:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('808080'));
            $sheet->getStyle("A9:K{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            for ($row = 9; $row <= $lastRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(88);
            }
            $sheet->getColumnDimension('J')->setAutoSize(false)->setWidth(28);
            $sheet->getColumnDimension('K')->setAutoSize(false)->setWidth(35);
            $sheet->getStyle("G9:G{$lastRow}")->getNumberFormat()->setFormatCode('₱#,##0.00');
            $sheet->getRowDimension(8)->setRowHeight(32);
            $sheet->freezePane('A9');
            $sheet->setAutoFilter('A8:K'.$lastRow);
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A3)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(.35)->setRight(.2)->setBottom(.35)->setLeft(.2);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 8);
        }];
    }
}
