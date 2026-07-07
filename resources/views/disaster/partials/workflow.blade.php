@php
    $workflowSteps = $workflowSteps ?? [
        ['label' => 'TCISS Verified', 'status' => 'current'],
        ['label' => 'DAFAC Intake', 'status' => 'pending'],
        ['label' => 'Duplicate Checked', 'status' => 'pending'],
        ['label' => 'Validated', 'status' => 'pending'],
        ['label' => 'Payroll Ready', 'status' => 'pending'],
        ['label' => 'Payout Scheduled', 'status' => 'pending'],
        ['label' => 'Released', 'status' => 'pending'],
        ['label' => 'Requirements Completed', 'status' => 'pending'],
    ];
@endphp

<div class="d-flex flex-wrap gap-3">
    @foreach ($workflowSteps as $index => $step)
        @php
            $isDone = ($step['status'] ?? 'pending') === 'done';
            $isCurrent = ($step['status'] ?? 'pending') === 'current';
            $badgeClass = $isDone ? 'badge-light-success' : ($isCurrent ? 'badge-light-primary' : 'badge-light');
            $iconClass = $isDone ? 'ki-check-circle text-success' : ($isCurrent ? 'ki-right-circle text-primary' : 'ki-time text-muted');
        @endphp
        <div class="d-flex align-items-center gap-2 bg-body rounded border border-dashed border-gray-300 px-4 py-3">
            <span class="badge {{ $badgeClass }} fw-bold">{{ $index + 1 }}</span>
            <i class="ki-duotone {{ $iconClass }} fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <span class="fw-semibold text-gray-800 fs-7">{{ $step['label'] }}</span>
        </div>
    @endforeach
</div>
