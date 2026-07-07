@extends('layouts.dashboard.main')

@section('content')
    @php
        $reports = [
            ['title' => 'DAFAC Printable Report', 'icon' => 'ki-document'],
            ['title' => 'List of Affected Families', 'icon' => 'ki-people'],
            ['title' => 'Validated Housing Damage Report', 'icon' => 'ki-home'],
            ['title' => 'Payroll-ready Report', 'icon' => 'ki-dollar'],
            ['title' => 'Payout / Distribution Report', 'icon' => 'ki-delivery'],
            ['title' => 'Missing Requirements Report', 'icon' => 'ki-folder-down'],
        ];
    @endphp

    <div class="row g-5 g-xl-8">
        @foreach ($reports as $report)
            <div class="col-md-6 col-xl-4">
                <div class="card card-flush shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <i class="ki-duotone {{ $report['icon'] }} fs-3x text-danger mb-6">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <h3 class="fw-bold text-gray-900 mb-3">{{ $report['title'] }}</h3>
                        <div class="text-muted mb-8">Generate, filter, print, or export this operational report.</div>
                        <div class="mt-auto d-flex gap-3">
                            <button class="btn btn-light-primary btn-sm">Preview</button>
                            <button class="btn btn-light-success btn-sm">Export</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
