@extends('layouts.dashboard.main')

@section('content')
    @php
        $rows = [
            ['head' => 'Maria Santos', 'barangay' => 'Western Bicutan', 'address' => 'Blk 12 Lot 8 Phase 2', 'center' => 'City University Gym', 'housing' => 'Partially Damaged', 'status' => 'TCISS_VERIFIED'],
            ['head' => 'Roberto Cruz', 'barangay' => 'Ususan', 'address' => 'Purok 4 Riverside', 'center' => 'Ususan Covered Court', 'housing' => 'Water Damage', 'status' => 'NEEDS_REVIEW'],
        ];
    @endphp

    <div class="card card-flush shadow-sm mb-8">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">TCISS / Masterlist Verification</h3>
            </div>
            <div class="card-toolbar gap-3">
                <button type="button" class="btn btn-light-primary btn-sm">
                    <i class="ki-duotone ki-file-up fs-3"><span class="path1"></span><span class="path2"></span></i>
                    Import
                </button>
                <button type="button" class="btn btn-danger btn-sm">
                    <i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i>
                    Encode Family
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-5 mb-8">
                <div class="col-lg-4">
                    <input type="text" class="form-control form-control-solid" placeholder="Search household head, address, member name">
                </div>
                <div class="col-lg-3">
                    <select class="form-select form-select-solid">
                        <option>All barangays</option>
                        <option>Western Bicutan</option>
                        <option>Ususan</option>
                        <option>Tuktukan</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <select class="form-select form-select-solid">
                        <option>All evacuation centers</option>
                        <option>City University Gym</option>
                        <option>Ususan Covered Court</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-light w-100">Filter</button>
                </div>
            </div>

            @include('disaster.partials.module-table', ['rows' => $rows])
        </div>
    </div>
@endsection
