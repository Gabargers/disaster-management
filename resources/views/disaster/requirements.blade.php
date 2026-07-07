@extends('layouts.dashboard.main')

@section('content')
    <div class="card card-flush shadow-sm">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Post-Payout Requirements</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold text-uppercase fs-7">
                            <th>Household</th>
                            <th>BFP Certificate</th>
                            <th>Barangay Certification</th>
                            <th>Documents</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['Maria Santos', 'Roberto Cruz', 'Lorna Reyes'] as $index => $name)
                            <tr>
                                <td class="fw-bold">{{ $name }}</td>
                                <td><span class="badge badge-light-{{ $index === 0 ? 'success' : 'warning' }}">{{ $index === 0 ? 'Verified' : 'Pending' }}</span></td>
                                <td><span class="badge badge-light-{{ $index === 2 ? 'success' : 'warning' }}">{{ $index === 2 ? 'Submitted' : 'Pending' }}</span></td>
                                <td><button class="btn btn-sm btn-light-primary">Upload</button></td>
                                <td class="text-end"><button class="btn btn-sm btn-light-success">Mark Verified</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
