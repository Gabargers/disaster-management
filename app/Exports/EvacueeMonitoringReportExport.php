<?php

namespace App\Exports;

use App\Models\Disaster\Disaster;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class EvacueeMonitoringReportExport implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithTitle
{
    public function __construct(private Collection $rows, private array $columns, private array $selected, private ?Disaster $incident) {}

    public static function columns(): array
    {
        return ['district'=>'District','barangay'=>'Barangay','evacuation_center'=>'Evacuation Centers','families'=>'Family','individuals'=>'Individual','male'=>'Male','female'=>'Female','age_0_4'=>'0-4 y/o','age_5_17'=>'5-17 y/o','age_18_59'=>'18-59 y/o','age_60_plus'=>'60+','pwd'=>'PWD','solo_parent'=>'SP','lactating'=>'LM','pregnant'=>'PREG','four_ps'=>'4Ps','staff'=>'Staff'];
    }

    public function array(): array
    {
        $last=count($this->selected);
        $data=[
            array_pad(['REPUBLIC OF THE PHILIPPINES'],$last,''),
            array_pad(['CITY GOVERNMENT OF TAGUIG'],$last,''),
            array_pad(['CITY SOCIAL WELFARE AND DEVELOPMENT OFFICE'],$last,''),
            array_pad(['MONITORING REPORT OF EVACUEES'],$last,''),
            array_pad(['AS OF '.now()->format('F d, Y, l \A\T h:i A')],$last,''),
            array_pad(['Name of Incident: '.strtoupper($this->incident?->name ?? 'ALL INCIDENTS')],$last,''),
            array_pad(['Affected Area: TAGUIG CITY'],$last,''),
            array_pad(['Date of Incident: '.($this->incident?->incident_date?->format('F d, Y') ?? 'ALL DATES')],$last,''),
            array_map(fn($key)=>$this->columns[$key],$this->selected),
        ];
        foreach($this->rows as $row)$data[]=array_map(fn($key)=>$row[$key],$this->selected);
        $total=array_map(fn($key)=>in_array($key,['district','barangay','evacuation_center'],true)?($key===$this->selected[0]?'GRAND TOTAL':''):$this->rows->sum($key),$this->selected);
        $data[]=$total;
        $data[]=array_pad(['Legend: PWD - Person With Disability | SP - Solo Parent | LM - Lactating Mother | PREG - Pregnant Women | 4Ps - Pantawid Pamilya Pilipino Program'],$last,'');
        return $data;
    }

    public function title(): string { return 'Monitoring Report'; }

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
        return [AfterSheet::class=>function(AfterSheet $event){
            $sheet=$event->sheet->getDelegate(); $lastColumn=$sheet->getHighestColumn(); $lastRow=$sheet->getHighestRow(); $totalRow=$lastRow-1;
            foreach(range(1,8) as $row)$sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->mergeCells("A{$lastRow}:{$lastColumn}{$lastRow}");
            $sheet->getStyle("A1:{$lastColumn}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A1:{$lastColumn}4")->getFont()->setBold(true);
            $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setSize(14);
            $sheet->getRowDimension(1)->setRowHeight(20); $sheet->getRowDimension(2)->setRowHeight(20); $sheet->getRowDimension(3)->setRowHeight(20); $sheet->getRowDimension(4)->setRowHeight(24); $sheet->getRowDimension(5)->setRowHeight(20);
            $sheet->getStyle("A9:{$lastColumn}9")->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F4E78']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true]]);
            $sheet->getStyle("A9:{$lastColumn}{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('808080'));
            $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray(['font'=>['bold'=>true],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'D9EAF7']]]);
            $sheet->getStyle("A10:{$lastColumn}{$totalRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getRowDimension(9)->setRowHeight(32); $sheet->freezePane('A10'); $sheet->setAutoFilter("A9:{$lastColumn}{$totalRow}");
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(.35)->setRight(.25)->setBottom(.35)->setLeft(.25);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,9);
        }];
    }
}
