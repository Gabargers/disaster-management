<?php

namespace App\Exports;

use App\Models\Disaster\EvacuationCenter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EvacuationHistoryWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private EvacuationCenter $center,
        private Collection $familyRows,
        private Collection $payoutRows,
    ) {}

    public function sheets(): array
    {
        return [
            new EvacuationHistoryExport($this->center, $this->familyRows),
            new EvacuationHistoryPayoutPhotosExport(
                $this->payoutRows,
                $this->center->disaster_class_name ?: ($this->center->disaster?->name ?: '—'),
                'Closed '.($this->center->closed_at?->format('M d, Y h:i A') ?: '—'),
            ),
        ];
    }
}
