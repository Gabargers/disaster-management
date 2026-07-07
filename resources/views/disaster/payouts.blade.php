@extends('layouts.dashboard.main')

@section('content')
    <div class="card card-flush shadow-sm">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Payout Scheduling and Distribution</h3>
            </div>
            <div class="card-toolbar">
                <button class="btn btn-danger btn-sm">Create Schedule</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-6 mb-8">
                @foreach (['Payout Date', 'Assistance Kind', 'Quantity / Amount', 'Provider', 'Released By'] as $field)
                    <div class="col-md">
                        <label class="form-label fw-bold">{{ $field }}</label>
                        <input class="form-control form-control-solid" type="{{ $field === 'Payout Date' ? 'date' : 'text' }}">
                    </div>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold text-uppercase fs-7">
                            <th>Family</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Release Photo</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['Maria Santos' => 'Scheduled', 'Lorna Reyes' => 'Released'] as $name => $status)
                            <tr>
                                <td class="fw-bold">{{ $name }}</td>
                                <td>July 15, 2026 - City Hall Quadrangle</td>
                                <td><span class="badge badge-light-{{ $status === 'Released' ? 'success' : 'primary' }}">{{ $status }}</span></td>
                                <td><button class="btn btn-sm btn-light">Upload</button></td>
                                <td class="text-end"><button class="btn btn-sm btn-light-success">Mark Released</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
