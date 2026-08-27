<?php

namespace App\Exports;

use App\Models\Disaster\EvacuationCenter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class EvacuationCenterFamiliesExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(private EvacuationCenter $center, private Collection $rows, private string $filters = '') {}

    public function array(): array
    {
        $data=[
            [$this->center->name],
            ['ASSIGNED FAMILIES AND EVACUEES'],
            ['Barangay: '.($this->center->barangay?->name??'—')],
            ['Disaster: '.($this->center->disaster?->name??'—')],
            ['Generated: '.now()->format('F d, Y h:i A')],
            [$this->filters?:'Filters: All assigned families'],
            [],
            ['No.','Control Number','Household','Family Member','Relationship','Birthdate','Age','Sex','Occupation','Health Condition','Remarks','Complete Address','Barangay','Housing Condition','Ownership','Validation Status','Assigned Date','Household Total'],
        ];
        foreach($this->rows->values() as $index=>$row){
            $composition=collect([$row['head']])->concat($row['members']);
            foreach($composition as $memberIndex=>$member)$data[]=[
                $memberIndex===0?$index+1:'',$memberIndex===0?($row['control_number']?:'—'):'',$row['household_head'],$member['name'],$member['relationship'],$member['birthdate']?:'—',$member['age']??'—',$member['sex']?:'—',$member['occupation']?:'—',$member['health_condition']?:'—',$member['remarks']?:'—',$memberIndex===0?$row['address']:'',$memberIndex===0?$row['barangay']:'',$memberIndex===0?($row['housing_condition']?:'—'):'',$memberIndex===0?($row['house_ownership']?:'—'):'',$memberIndex===0?$row['validation_status']:'',$memberIndex===0?($row['assigned_at']?->format('Y-m-d')??'—'):'',$memberIndex===0?$row['household_size']:'',
            ];
        }
        $data[]=['','','GRAND TOTAL',$this->rows->sum('household_size').' INDIVIDUALS','','','','','','','','','','','','','',$this->rows->sum('household_size')];
        return $data;
    }

    public function title(): string { return 'Assigned Families'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class=>function(AfterSheet $event){$sheet=$event->sheet->getDelegate();$lastRow=$sheet->getHighestRow();foreach(range(1,6) as $row)$sheet->mergeCells("A{$row}:R{$row}");$sheet->getStyle('A1:R2')->getFont()->setBold(true);$sheet->getStyle('A1:R2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);$sheet->getStyle('A1')->getFont()->setSize(16);$sheet->getStyle('A8:R8')->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F4E78']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true]]);$sheet->getStyle("A8:R{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);for($row=9;$row<$lastRow;$row++){if($sheet->getCell("E{$row}")->getValue()==='Household Head')$sheet->getStyle("A{$row}:R{$row}")->applyFromArray(['font'=>['bold'=>true],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'EAF3F8']]]);}$sheet->getStyle("A{$lastRow}:R{$lastRow}")->applyFromArray(['font'=>['bold'=>true],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'D9EAF7']]]);$sheet->getStyle("A9:R{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);$sheet->freezePane('A9');$sheet->setAutoFilter("A8:R".max(8,$lastRow-1));$sheet->getRowDimension(8)->setRowHeight(34);$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A3)->setFitToWidth(1)->setFitToHeight(0);$sheet->getPageMargins()->setTop(.35)->setRight(.2)->setBottom(.35)->setLeft(.2);}];
    }
}
