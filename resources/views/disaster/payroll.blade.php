@extends('layouts.dashboard.main')

@section('content')
    @php
        $rows = [
            ['head' => 'Lorna Reyes', 'barangay' => 'Tuktukan', 'address' => 'Sampaguita Street', 'center' => 'Tuktukan ES', 'housing' => 'Totally Damaged', 'status' => 'PAYROLL_READY'],
            ['head' => 'Nestor Garcia', 'barangay' => 'Ususan', 'address' => 'Purok 2', 'center' => 'Ususan Covered Court', 'housing' => 'Partially Damaged', 'status' => 'VALIDATED'],
        ];
    @endphp

    <div class="card card-flush shadow-sm">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Final Cleaned List / Payroll Preparation</h3>
            </div>
            <div class="card-toolbar gap-3">
                <button class="btn btn-light-success btn-sm">Export Excel</button>
                <button class="btn btn-light-primary btn-sm">Export PDF</button>
                <button class="btn btn-danger btn-sm">Submit for Payroll</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-5 mb-8">
                @foreach (['Barangay', 'Disaster Type', 'Evacuation Center', 'Housing Condition', 'Ownership', 'Status'] as $filter)
                    <div class="col-md-2">
                        <select class="form-select form-select-solid">
                            <option>{{ $filter }}</option>
                        </select>
                    </div>
                @endforeach
            </div>
            @include('disaster.partials.module-table', ['rows' => $rows])
        </div>
    </div>
@endsection
