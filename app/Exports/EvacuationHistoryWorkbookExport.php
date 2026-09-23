<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EvacuationHistoryWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private Collection $historyRows,
        private array $columns,
        private array $selected,
        private string $incidentLabel,
        private string $closurePeriod,
        private Collection $payoutRows,
    ) {}

    public function sheets(): array
    {
        return [
            new EvacuationHistoryExport($this->historyRows, $this->columns, $this->selected, $this->incidentLabel, $this->closurePeriod),
            new EvacuationHistoryPayoutPhotosExport($this->payoutRows, $this->incidentLabel, $this->closurePeriod),
        ];
    }
}
