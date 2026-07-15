@extends('layouts.dashboard.main')

@section('content')
@php
    $mainCards = [
        ['TOTAL', 'Affected Families', 'All registered households', 'ki-people', 'primary', route('disaster.reports.index')],
        ['DUPLICATE_CHECK_PENDING', 'Duplicate Review', 'Waiting for identity review', 'ki-copy', 'warning', route('disaster.duplicates.index')],
        ['VALIDATION_PENDING', 'For Validation', 'Ready for field validation', 'ki-shield-tick', 'info', route('disaster.validation.index')],
        ['PAYROLL_READY', 'Payroll Ready', 'Ready for payroll submission', 'ki-dollar', 'success', route('disaster.payroll.index')],
    ];
    $attentionCards = [
        ['POSSIBLE_DUPLICATE', 'Possible duplicates', 'danger', route('disaster.duplicates.index')],
        ['NEEDS_CORRECTION', 'Needs correction', 'warning', route('disaster.validation.index')],
        ['REJECTED', 'Rejected records', 'danger', route('disaster.reports.index')],
        ['REQUIREMENTS_PENDING', 'Missing requirements', 'warning', route('disaster.requirements.index')],
    ];
    $stages = [
        ['DAFAC', 'DAFAC_INTAKE_COMPLETED', 'ki-notepad-edit', route('disaster.dafac.index')],
        ['Duplicate Check', 'DUPLICATE_CHECK_PENDING', 'ki-copy', route('disaster.duplicates.index')],
        ['Validation', 'VALIDATION_PENDING', 'ki-shield-tick', route('disaster.validation.index')],
        ['Payroll', 'SUBMITTED_FOR_PAYROLL', 'ki-dollar', route('disaster.payroll.index')],
        ['Payout', 'PAYOUT_SCHEDULED', 'ki-calendar-8', route('disaster.payouts.index')],
        ['Released', 'ASSISTANCE_RELEASED', 'ki-check-circle', route('disaster.payouts.index')],
        ['Completed', 'REQUIREMENTS_COMPLETED', 'ki-verify', route('disaster.requirements.index')],
    ];
@endphp

<div class="dashboard-hero rounded-4 p-7 p-lg-9 mb-7 text-white position-relative overflow-hidden">
    <div class="position-relative z-index-2 d-flex flex-column flex-lg-row justify-content-between gap-6 align-items-lg-center">
        <div>
            <div class="text-white text-opacity-75 fw-semibold mb-2">DISASTER ASSISTANCE OPERATIONS</div>
            <h1 class="text-white fw-bold mb-2">Good day, {{ auth()->user()->first_name ?: auth()->user()->name }}</h1>
            <div class="text-white text-opacity-75 fs-6">Monitor households and move urgent cases through the assistance workflow.</div>
        </div>
        @can('manage dafac intake')
            <a href="{{ route('disaster.dafac.index') }}" class="btn btn-light btn-lg fw-bold text-primary flex-shrink-0">
                <i class="ki-duotone ki-plus fs-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                New DAFAC Intake
            </a>
        @endcan
    </div>
</div>

<div class="card card-flush shadow-sm mb-7">
    <div class="card-body py-5">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label fs-7 fw-bold text-muted">DISASTER</label>
                <select name="disaster_id" class="form-select form-select-solid">
                    <option value="">All disasters</option>
                    @foreach($disasters as $item)<option value="{{ $item->id }}" @selected(request('disaster_id') == $item->id)>{{ $item->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label fs-7 fw-bold text-muted">BARANGAY</label>
                <select name="barangay_id" class="form-select form-select-solid">
                    <option value="">All barangays</option>
                    @foreach($barangays as $item)<option value="{{ $item->id }}" @selected(request('barangay_id') == $item->id)>{{ $item->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-xl-2"><label class="form-label fs-7 fw-bold text-muted">FROM</label><input name="date_from" value="{{ request('date_from') }}" type="date" class="form-control form-control-solid"></div>
            <div class="col-6 col-md-4 col-xl-2"><label class="form-label fs-7 fw-bold text-muted">TO</label><input name="date_to" value="{{ request('date_to') }}" type="date" class="form-control form-control-solid"></div>
            <div class="col-12 col-md-4 col-xl-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Apply</button>@if(request()->hasAny(['disaster_id','barangay_id','date_from','date_to']))<a href="{{ route('dashboard') }}" class="btn btn-light btn-icon" title="Clear filters"><i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i></a>@endif</div>
        </form>
    </div>
</div>

<div class="row g-5 mb-7">
    @foreach($mainCards as [$key, $label, $description, $icon, $tone, $url])
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ $url }}{{ $key === 'TOTAL' ? '' : '?status='.$key }}" class="card metric-card card-flush shadow-sm h-100 border-start border-4 border-{{ $tone }}">
                <div class="card-body p-6">
                    <div class="d-flex justify-content-between align-items-start mb-5">
                        <div class="symbol symbol-45px"><div class="symbol-label bg-light-{{ $tone }}"><i class="ki-duotone {{ $icon }} fs-2x text-{{ $tone }}"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></div></div>
                        <i class="ki-duotone ki-arrow-right fs-2 text-muted"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="fs-2hx fw-bold text-gray-900 mb-1">{{ number_format($metrics[$key] ?? 0) }}</div>
                    <div class="fw-bold text-gray-800">{{ $label }}</div>
                    <div class="text-muted fs-7 mt-1">{{ $description }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-7 mb-7">
    <div class="col-xl-9">
        <div class="card card-flush shadow-sm h-100">
            <div class="card-header"><div class="card-title"><div><h3 class="fw-bold mb-1">Assistance Workflow</h3><div class="text-muted fs-7">Select a stage to open its current queue</div></div></div></div>
            <div class="card-body pt-2">
                <div class="workflow-track">
                    @foreach($stages as [$label, $key, $icon, $url])
                        <a href="{{ $url }}?status={{ $key }}" class="workflow-stage text-center">
                            <div class="workflow-icon mx-auto"><i class="ki-duotone {{ $icon }} fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></div>
                            <div class="fs-2 fw-bold text-gray-900 mt-3">{{ number_format($metrics[$key] ?? 0) }}</div>
                            <div class="fs-7 fw-semibold text-muted">{{ $label }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush shadow-sm h-100">
            <div class="card-header"><div class="card-title"><h3 class="fw-bold">Needs Attention</h3></div></div>
            <div class="card-body pt-1">
                @foreach($attentionCards as [$key, $label, $tone, $url])
                    <a href="{{ $url }}?status={{ $key }}" class="d-flex align-items-center justify-content-between rounded bg-light-{{ $tone }} px-4 py-3 mb-3">
                        <span class="fw-semibold text-gray-800">{{ $label }}</span><span class="badge badge-{{ $tone }}">{{ number_format($metrics[$key] ?? 0) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card card-flush shadow-sm">
    <div class="card-header align-items-center">
        <div class="card-title"><div><h3 class="fw-bold mb-1">Recent Household Records</h3><div class="text-muted fs-7">Latest activity across all assistance stages</div></div></div>
        <div class="card-toolbar"><a href="{{ route('disaster.reports.index') }}" class="btn btn-sm btn-light-primary">View All Records <i class="ki-duotone ki-arrow-right fs-4"><span class="path1"></span><span class="path2"></span></i></a></div>
    </div>
    <div class="card-body pt-0">@include('disaster.partials.family-table', ['families' => $families])</div>
</div>
@endsection

@push('styles')
<style>
    .dashboard-hero{background:linear-gradient(120deg,#4a0d18 0%,#731c2b 58%,#9b2c3f 100%)}
    .dashboard-hero:after{content:"";position:absolute;width:360px;height:360px;border:70px solid rgba(255,255,255,.08);border-radius:50%;right:-80px;top:-170px}
    .metric-card{transition:transform .18s ease,box-shadow .18s ease}.metric-card:hover{transform:translateY(-3px);box-shadow:0 .75rem 1.5rem rgba(0,0,0,.09)!important}
    .workflow-track{display:grid;grid-template-columns:repeat(7,1fr);position:relative;gap:8px}.workflow-track:before{content:"";position:absolute;height:2px;background:#e4e6ef;left:7%;right:7%;top:27px}
    .workflow-stage{position:relative;z-index:1;padding:0 4px}.workflow-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f1f6ff;color:#1877c9;border:4px solid #fff;box-shadow:0 2px 9px rgba(24,119,201,.15);transition:.18s}.workflow-stage:hover .workflow-icon{background:#1877c9;color:#fff;transform:scale(1.06)}
    @media(max-width:991.98px){.workflow-track{grid-template-columns:repeat(4,1fr);row-gap:28px}.workflow-track:before{display:none}}
    @media(max-width:575.98px){.workflow-track{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush
