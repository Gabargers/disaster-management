@extends('layouts.dashboard.main')

@section('content')
@php
    $mainCards = [
        ['FAMILY_AFFECTED', 'Family Affected', 'TCISS families received and ready for assignment', 'ki-people', 'primary', route('disaster.person-affecteds.index')],
        ['VALIDATION_PENDING', 'For Validation', 'DAFAC households awaiting validation', 'ki-shield-tick', 'warning', route('disaster.payouts.index')],
        ['PAYOUT_SCHEDULED', 'Scheduled Payouts', 'Households scheduled for release', 'ki-calendar-8', 'info', route('disaster.payouts.index')],
        ['RELEASED_PAYOUTS', 'Released Payouts', 'Households that received assistance', 'ki-dollar', 'success', route('disaster.payroll.index')],
        ['ASSIGNED_FAMILIES', 'Assigned Families', ($metrics['ACTIVE_EVACUATION_CENTERS'] ?? 0).' active evacuation centers', 'ki-geolocation', 'danger', route('disaster.payouts.index')],
    ];
    $attentionCards = [
        ['VALIDATION_PENDING', 'For validation', 'warning', route('disaster.payouts.index')],
        ['NEEDS_CORRECTION', 'Needs correction', 'danger', route('disaster.payouts.index')],
        ['PAYOUT_PENDING', 'Pending payout schedule', 'info', route('disaster.payouts.index')],
        ['REQUIREMENTS_PENDING', 'Missing requirements', 'warning', route('disaster.payroll.index')],
    ];
    $stages = [
        ['Family Affected', 'FAMILY_AFFECTED', 'ki-people', route('disaster.person-affecteds.index')],
        ['Assigned Families', 'ASSIGNED_FAMILIES', 'ki-geolocation', route('disaster.payouts.index')],
        ['For Validation', 'VALIDATION_PENDING', 'ki-shield-tick', route('disaster.payouts.index')],
        ['Validated', 'VALIDATED', 'ki-check-circle', route('disaster.payouts.index')],
        ['Payout Pending', 'PAYOUT_PENDING', 'ki-time', route('disaster.payouts.index')],
        ['Scheduled', 'PAYOUT_SCHEDULED', 'ki-calendar-8', route('disaster.payouts.index')],
        ['Released', 'RELEASED_PAYOUTS', 'ki-dollar', route('disaster.payroll.index')],
        ['Requirements', 'REQUIREMENTS_PENDING', 'ki-folder-down', route('disaster.payroll.index')],
    ];
@endphp

<div class="dashboard-hero rounded-4 p-7 p-lg-9 mb-7 text-white position-relative overflow-hidden">
    <div class="position-relative z-index-2 d-flex flex-column flex-lg-row justify-content-between gap-6 align-items-lg-center">
        <div>
            <div class="text-white text-opacity-75 fw-semibold mb-2">DISASTER ASSISTANCE OPERATIONS</div>
            <h1 class="text-white fw-bold mb-2">Good day, {{ auth()->user()->first_name ?: auth()->user()->name }}</h1>
            <div class="text-white text-opacity-75 fs-6">Monitor households and move urgent cases through the assistance workflow.</div>
        </div>
        @can('manage payout schedules')
            <a href="{{ route('disaster.payouts.index') }}" class="btn btn-light btn-lg fw-bold text-primary flex-shrink-0">
                <i class="ki-duotone ki-geolocation fs-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                Open Evacuation Centers
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

<div class="dashboard-metrics-grid mb-7">
    @foreach($mainCards as [$key, $label, $description, $icon, $tone, $url])
        <a href="{{ $url }}{{ in_array($key, ['FAMILY_AFFECTED', 'ASSIGNED_FAMILIES'], true) ? '' : '?status='.$key }}" class="card metric-card metric-card-{{ $tone }} card-flush shadow-sm h-100">
            <div class="card-body">
                <div class="metric-card-top">
                    <div class="metric-icon bg-light-{{ $tone }}"><i class="ki-duotone {{ $icon }} fs-2x text-{{ $tone }}"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></div>
                    <span class="metric-arrow"><i class="ki-duotone ki-arrow-up-right fs-3"><span class="path1"></span><span class="path2"></span></i></span>
                </div>
                <div class="metric-value">{{ number_format($metrics[$key] ?? 0) }}</div>
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-description">{{ $description }}</div>
            </div>
        </a>
    @endforeach
</div>

<div class="row g-7 mb-7">
    <div class="col-xl-9">
        <div class="card workflow-card card-flush shadow-sm h-100">
            <div class="card-header align-items-center border-0 pb-0"><div class="card-title"><div><h3 class="fw-bold mb-1">Assistance Workflow</h3><div class="text-muted fs-7">Select a stage to open its current queue</div></div></div><div class="card-toolbar"><span class="badge badge-light-primary">Live workflow</span></div></div>
            <div class="card-body pt-5">
                <div class="workflow-track">
                    @foreach($stages as [$label, $key, $icon, $url])
                        <a href="{{ $url }}{{ in_array($key, ['FAMILY_AFFECTED','RELEASED_PAYOUTS','ASSIGNED_FAMILIES'], true) ? '' : '?status='.$key }}" class="workflow-stage text-center">
                            <div class="workflow-icon mx-auto"><i class="ki-duotone {{ $icon }} fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></div>
                            <div class="workflow-count">{{ number_format($metrics[$key] ?? 0) }}</div>
                            <div class="workflow-label">{{ $label }}</div>
                            <div class="workflow-open">Open <i class="ki-duotone ki-arrow-right fs-5"><span class="path1"></span><span class="path2"></span></i></div>
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

@endsection

@push('styles')
<style>
    .dashboard-hero{background:linear-gradient(120deg,#4a0d18 0%,#731c2b 58%,#9b2c3f 100%)}
    .dashboard-hero:after{content:"";position:absolute;width:360px;height:360px;border:70px solid rgba(255,255,255,.08);border-radius:50%;right:-80px;top:-170px}
    .dashboard-metrics-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1.25rem}
    .metric-card{position:relative;overflow:hidden;border:1px solid #edf0f5;border-top:3px solid transparent;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
    .metric-card:after{content:"";position:absolute;width:90px;height:90px;border-radius:50%;right:-42px;bottom:-48px;background:currentColor;opacity:.045;pointer-events:none}
    .metric-card-primary{border-top-color:#3e97ff;color:#3e97ff}.metric-card-warning{border-top-color:#ffc700;color:#ffc700}.metric-card-info{border-top-color:#7239ea;color:#7239ea}.metric-card-success{border-top-color:#50cd89;color:#50cd89}.metric-card-danger{border-top-color:#f1416c;color:#f1416c}
    .metric-card .card-body{display:flex;min-height:205px;padding:1.55rem;flex-direction:column}
    .metric-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
    .metric-icon{display:flex;width:46px;height:46px;border-radius:12px;align-items:center;justify-content:center}
    .metric-arrow{display:flex;width:32px;height:32px;border-radius:50%;align-items:center;justify-content:center;color:#a1a5b7;background:#f6f8fb;transition:.18s}
    .metric-value{color:#181c32;font-size:2rem;line-height:1;font-weight:800;letter-spacing:-.04em;margin-bottom:.55rem}
    .metric-label{color:#3f4254;font-size:.95rem;line-height:1.3;font-weight:700;margin-bottom:.35rem}
    .metric-description{color:#7e8299;font-size:.78rem;line-height:1.45;margin-top:auto}
    .metric-card:hover{transform:translateY(-4px);border-color:currentColor;box-shadow:0 .8rem 1.8rem rgba(24,28,50,.09)!important}
    .metric-card:hover .metric-arrow{color:#fff;background:#181c32}.metric-card:hover .metric-arrow i{color:#fff!important}
    .workflow-card{border:1px solid #edf0f5}
    .workflow-track{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));position:relative;gap:.65rem}.workflow-track:before{content:"";position:absolute;height:2px;background:linear-gradient(90deg,#dbeafe,#c7d2fe,#dbeafe);left:5%;right:5%;top:31px}
    .workflow-stage{position:relative;z-index:1;display:flex;min-width:0;padding:.45rem .25rem .75rem;flex-direction:column;align-items:center;border-radius:12px;transition:background .18s ease,transform .18s ease}
    .workflow-icon{display:flex;width:62px;height:62px;border-radius:18px;align-items:center;justify-content:center;color:#1877c9;background:#f1f6ff;border:5px solid #fff;box-shadow:0 4px 14px rgba(24,119,201,.14);transition:background .18s,color .18s,transform .18s}
    .workflow-count{color:#181c32;font-size:1.45rem;line-height:1;font-weight:800;margin-top:1rem}
    .workflow-label{min-height:2.2rem;color:#5e6278;font-size:.76rem;line-height:1.25;font-weight:700;margin-top:.4rem;display:flex;align-items:flex-start;justify-content:center}
    .workflow-open{display:flex;align-items:center;gap:.2rem;color:#a1a5b7;font-size:.7rem;font-weight:600;margin-top:.45rem;opacity:0;transform:translateY(3px);transition:.18s}
    .workflow-stage:hover{background:#f8faff;transform:translateY(-2px)}.workflow-stage:hover .workflow-icon{background:#1877c9;color:#fff;transform:scale(1.04)}.workflow-stage:hover .workflow-open{color:#1877c9;opacity:1;transform:translateY(0)}
    @media(max-width:1399.98px){.dashboard-metrics-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:1199.98px){.workflow-track{grid-template-columns:repeat(4,1fr);row-gap:1rem}.workflow-track:before{display:none}.workflow-open{opacity:1;transform:none}}
    @media(max-width:991.98px){.dashboard-metrics-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}}
    @media(max-width:575.98px){.dashboard-metrics-grid{grid-template-columns:1fr}.metric-card .card-body{min-height:180px}.workflow-track{grid-template-columns:repeat(2,1fr)}.workflow-stage{padding:.65rem .25rem}.workflow-icon{width:56px;height:56px}}
</style>
@endpush

@push('scripts')
<script>
    // Browser back/forward cache can restore the dashboard with the count from
    // before a payout was released. Reload only when the page was restored
    // from that cache so the operational metrics always reflect the database.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
@endpush
