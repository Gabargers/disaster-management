@extends('layouts.dashboard.main')

@section('content')
<div class="map-page-toolbar d-flex flex-wrap align-items-center justify-content-between gap-4 mb-7">
    <div>
        <div class="text-primary fw-bold fs-7 text-uppercase mb-1">Live monitoring · Viewing only</div>
        <h2 class="fw-bold text-gray-900 mb-1">Evacuation Center Overview</h2>
        <div class="text-gray-600">Addresses and current assigned-family counts for every active evacuation area.</div>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-light-primary fw-bold">
        <i class="ki-duotone ki-arrow-left fs-2"><span class="path1"></span><span class="path2"></span></i>
        Back to Dashboard
    </a>
</div>

<div class="row g-7">
    <div class="col-xl-8">
        <div class="card card-flush shadow-sm map-shell h-100">
            <div class="card-body p-0">
                <div id="evacuation-map"
                    data-boundary-url="{{ asset('map/oca-bbm-barangays.geojson') }}"
                    data-centers-url="{{ route('evacuation-map.centers') }}"
                    aria-label="Taguig barangay and evacuation center map"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm h-100 center-panel">
            <div class="card-header border-0">
                <div class="card-title d-block py-5">
                    <h3 class="fw-bold mb-1">Evacuation Areas</h3>
                    <div class="text-muted fs-7">{{ number_format($centers->count()) }} active centers</div>
                </div>
                <div class="card-toolbar"><span class="badge badge-light-success"><span class="live-dot"></span>Live</span></div>
            </div>
            <div class="card-body pt-0 center-list">
                @forelse($centers as $center)
                    <div class="center-item">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="fw-bold text-gray-900 mb-1">{{ $center->name }}</div>
                                <div class="text-muted fs-7 d-flex align-items-start gap-2">
                                    <i class="ki-duotone ki-geolocation fs-5 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <span>{{ $center->address ?: $center->barangay?->name ?: 'Address pending' }}</span>
                                </div>
                            </div>
                            <div class="live-count flex-shrink-0">
                                <strong>{{ number_format($center->live_family_count) }}</strong>
                                <span>families</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <i class="ki-duotone ki-information-5 fs-3x text-muted"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <h4 class="mt-4 mb-1">No active centers</h4>
                        <div class="text-muted">Active evacuation areas will appear here.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/evacuation-map.css') }}">
<style>
    .map-page-toolbar{position:relative;z-index:2;margin-top:1.5rem;padding:1.5rem 1.75rem;border:1px solid #edf0f5;border-radius:1rem;background:var(--bs-body-bg,#fff);box-shadow:0 5px 18px rgba(24,28,50,.06)}
    .map-shell,.center-panel{border:1px solid #edf0f5;min-height:620px}.map-shell{overflow:hidden}.map-shell #evacuation-map{min-height:620px}
    .center-list{max-height:535px;overflow:auto}.center-item{padding:1.25rem 0;border-top:1px dashed #e4e6ef}.center-item:first-child{border-top:0}.live-count{min-width:76px;padding:.55rem .7rem;text-align:center;border-radius:10px;color:#0f8a55;background:#e8fff3}.live-count strong,.live-count span{display:block}.live-count strong{font-size:1.15rem;line-height:1.1}.live-count span{font-size:.68rem;font-weight:600}.live-dot{display:inline-block;width:7px;height:7px;margin-right:.4rem;border-radius:50%;background:#50cd89;box-shadow:0 0 0 4px rgba(80,205,137,.15)}
    @media(max-width:1199.98px){.map-shell,.center-panel,.map-shell #evacuation-map{min-height:500px}.center-list{max-height:none}}
    @media(max-width:575.98px){.map-page-toolbar{margin-top:.75rem;padding:1.25rem}.map-page-toolbar .btn{width:100%;justify-content:center}}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.js') }}"></script>
<script src="{{ asset('assets/js/evacuation-map.js') }}"></script>
<script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) window.location.reload();
    });
</script>
@endpush
