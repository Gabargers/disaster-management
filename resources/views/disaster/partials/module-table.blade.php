@php
    $rows = $rows ?? [];
@endphp

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-4">
        <thead>
            <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0">
                <th>Household Head</th>
                <th>Barangay</th>
                <th>Evacuation Center</th>
                <th>Housing</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody class="fw-semibold text-gray-800">
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">{{ $row['head'] }}</span>
                            <span class="text-muted fs-7">{{ $row['address'] }}</span>
                        </div>
                    </td>
                    <td>{{ $row['barangay'] }}</td>
                    <td>{{ $row['center'] }}</td>
                    <td>{{ $row['housing'] }}</td>
                    <td>@include('disaster.partials.status-badge', ['status' => $row['status']])</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-light-primary">Open</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-10">No records to show yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
