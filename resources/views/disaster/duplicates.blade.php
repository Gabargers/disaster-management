@extends('layouts.dashboard.main')

@section('content')
    <div class="card card-flush shadow-sm">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Duplicate Checking</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-8">
                <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <div>
                    <h4 class="fw-bold text-gray-900">Possible match detected</h4>
                    <div class="text-gray-700">Matched by household head name, birthdate, address, barangay, and family member names.</div>
                </div>
            </div>

            <div class="row g-6">
                @foreach (['Current Intake' => 'Maria Santos - Western Bicutan', 'Possible Duplicate' => 'Maria L. Santos - Western Bicutan'] as $title => $body)
                    <div class="col-lg-6">
                        <div class="border rounded p-6 h-100">
                            <div class="d-flex justify-content-between mb-5">
                                <h4 class="fw-bold mb-0">{{ $title }}</h4>
                                @include('disaster.partials.status-badge', ['status' => $title === 'Current Intake' ? 'DUPLICATE_CHECKED' : 'NEEDS_REVIEW'])
                            </div>
                            <div class="fs-5 fw-semibold text-gray-900 mb-2">{{ $body }}</div>
                            <div class="text-muted mb-4">Blk 12 Lot 8 Phase 2, City University Gym</div>
                            @include('disaster.partials.workflow')
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end gap-3 mt-8">
                <button type="button" class="btn btn-light-danger">Mark Duplicate</button>
                <button type="button" class="btn btn-light-primary">Merge Record</button>
                <button type="button" class="btn btn-success">Approve Separate Household</button>
            </div>
        </div>
    </div>
@endsection
