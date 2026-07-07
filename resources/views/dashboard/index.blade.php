@extends('layouts.dashboard.main')
@section('content')
    @php
        $metrics = [
            ['label' => 'Total Affected Families', 'value' => '1,284', 'icon' => 'ki-people', 'tone' => 'primary'],
            ['label' => 'Pending DAFAC Intake', 'value' => '326', 'icon' => 'ki-notepad-edit', 'tone' => 'warning'],
            ['label' => 'Pending Validation', 'value' => '218', 'icon' => 'ki-shield-tick', 'tone' => 'info'],
            ['label' => 'Duplicates Detected', 'value' => '42', 'icon' => 'ki-copy', 'tone' => 'danger'],
            ['label' => 'Ready for Payroll', 'value' => '518', 'icon' => 'ki-dollar', 'tone' => 'success'],
            ['label' => 'Scheduled Payouts', 'value' => '371', 'icon' => 'ki-calendar-8', 'tone' => 'primary'],
            ['label' => 'Completed Payouts', 'value' => '249', 'icon' => 'ki-check-circle', 'tone' => 'success'],
        ];

        $activityRows = [
            ['head' => 'Maria Santos', 'barangay' => 'Western Bicutan', 'address' => 'Blk 12 Lot 8 Phase 2', 'center' => 'City University Gym', 'housing' => 'Partially Damaged', 'status' => 'VALIDATED'],
            ['head' => 'Roberto Cruz', 'barangay' => 'Ususan', 'address' => 'Purok 4 Riverside', 'center' => 'Ususan Covered Court', 'housing' => 'Water Damage', 'status' => 'DUPLICATE_CHECKED'],
            ['head' => 'Lorna Reyes', 'barangay' => 'Tuktukan', 'address' => 'Sampaguita Street', 'center' => 'Tuktukan ES', 'housing' => 'Totally Damaged', 'status' => 'PAYROLL_READY'],
        ];
    @endphp

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($metrics as $metric)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-flush h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-5">
                        <div class="symbol symbol-55px">
                            <div class="symbol-label bg-light-{{ $metric['tone'] }}">
                                <i class="ki-duotone {{ $metric['icon'] }} fs-2x text-{{ $metric['tone'] }}">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </div>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-gray-900">{{ $metric['value'] }}</div>
                            <div class="fs-7 fw-semibold text-muted">{{ $metric['label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card card-flush shadow-sm mb-8">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Family Assistance Workflow</h3>
            </div>
        </div>
        <div class="card-body">
            @include('disaster.partials.workflow', [
                'workflowSteps' => [
                    ['label' => 'TCISS Verified', 'status' => 'done'],
                    ['label' => 'DAFAC Intake', 'status' => 'done'],
                    ['label' => 'Duplicate Checked', 'status' => 'current'],
                    ['label' => 'Validated', 'status' => 'pending'],
                    ['label' => 'Payroll Ready', 'status' => 'pending'],
                    ['label' => 'Payout Scheduled', 'status' => 'pending'],
                    ['label' => 'Released', 'status' => 'pending'],
                    ['label' => 'Requirements Completed', 'status' => 'pending'],
                ],
            ])
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-xl-8">
            <div class="card card-flush shadow-sm h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Priority Household Queue</h3>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('disaster.tciss.index') }}" class="btn btn-sm btn-danger">
                            <i class="ki-duotone ki-plus fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Verify Family
                        </a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('disaster.partials.module-table', ['rows' => $activityRows])
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush shadow-sm h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Operational Alerts</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-6">
                        <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="fw-semibold">
                            <h4 class="text-gray-900 fw-bold">42 possible duplicates</h4>
                            <div class="fs-6 text-gray-700">Review duplicate matches before validation and payroll submission.</div>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-4">
                        <a href="{{ route('disaster.validation.index') }}" class="btn btn-light-primary text-start">Open Validation Queue</a>
                        <a href="{{ route('disaster.payroll.index') }}" class="btn btn-light-success text-start">Prepare Payroll List</a>
                        <a href="{{ route('disaster.requirements.index') }}" class="btn btn-light-warning text-start">Check Missing Requirements</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
