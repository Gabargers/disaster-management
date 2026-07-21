@extends('layouts.dashboard.main')

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header align-items-center">
        <div class="card-title"><div><h3 class="fw-bold mb-1">Activity Logs</h3><div class="text-muted fs-7">Account activity from coordinators, operational staff, administrators, and superadmins.</div></div></div>
        <div class="card-toolbar"><span class="badge badge-light-danger">Superadmin only</span></div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="activity-logs-table" class="table align-middle table-row-dashed fs-6 gy-5" style="width:100%">
                <thead><tr class="text-gray-700 fw-bold fs-7 text-uppercase"><th>Date & Time</th><th>Account</th><th>Role</th><th>Module</th><th>Action</th><th>Result</th><th>IP Address</th><th>Details</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><h2 class="mb-1">Activity Details</h2><div id="log-subtitle" class="text-muted fs-7"></div></div><button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2"></i></button></div>
        <div class="modal-body">
            <div id="log-summary" class="row g-5 mb-7"></div>
            <div class="row g-5">
                <div class="col-md-6"><h5>Previous Values</h5><pre id="log-old-values" class="bg-light rounded p-5 mb-0 text-wrap min-h-150px"></pre></div>
                <div class="col-md-6"><h5>Request / New Values</h5><pre id="log-new-values" class="bg-light rounded p-5 mb-0 text-wrap min-h-150px"></pre></div>
            </div>
            <div class="mt-6"><div class="text-muted fs-7 mb-1">Browser / Device</div><div id="log-user-agent" class="text-gray-800 text-break"></div></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
@endpush

@push('scripts')
<script>
$(function () {
    const table = $('#activity-logs-table').DataTable({
        processing: true, serverSide: true, responsive: true, searchDelay: 400, pageLength: 25,
        ajax: @json(route('activity-logs.data')), order: [[0, 'desc']],
        columns: [
            {data:'created_at', name:'created_at'}, {data:'account', name:'account', orderable:false},
            {data:'roles', name:'roles', orderable:false}, {data:'module', name:'module', orderable:false},
            {data:'action', name:'action'}, {data:'result', name:'result', orderable:false, searchable:false},
            {data:'ip_address', name:'ip_address'}, {data:'details', name:'details', orderable:false, searchable:false}
        ],
        language: {search:'', searchPlaceholder:'Search user, action, route, or IP...', emptyTable:'No account activity has been recorded yet.'}
    });

    const modal = new bootstrap.Modal(document.getElementById('activityLogModal'));
    const esc = value => $('<div>').text(value ?? '—').html();
    document.addEventListener('click', event => {
        const button = event.target.closest('.js-view-log');
        if (!button) return;
        const log = JSON.parse(button.dataset.log);
        $('#log-subtitle').text(`${log.action} · ${log.date}`);
        $('#log-summary').html(Object.entries({Account:log.account, Email:log.email, Roles:(log.roles||[]).join(', '), Module:log.module, 'IP Address':log.ip_address}).map(([label,value]) => `<div class="col-md-4"><div class="text-muted fs-7">${esc(label)}</div><div class="fw-semibold">${esc(value)}</div></div>`).join(''));
        $('#log-old-values').text(JSON.stringify(log.old_values || {}, null, 2));
        $('#log-new-values').text(JSON.stringify(log.new_values || {}, null, 2));
        $('#log-user-agent').text(log.user_agent || 'Not available');
        modal.show();
    });
});
</script>
@endpush
