@extends('layouts.dashboard.main')

@section('content')
    <div class="card card-flush shadow-sm mb-8">
        <div class="card-header">
            <div class="card-title"><h3 class="fw-bold mb-0">TCISS / Masterlist Verification</h3></div>
            <div class="card-toolbar gap-3">
                <button type="button" class="btn btn-light-primary btn-sm"><i class="ki-duotone ki-file-up fs-3"><span class="path1"></span><span class="path2"></span></i>Import</button>
                <button type="button" class="btn btn-danger btn-sm"><i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i>Encode Family</button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-5 mb-8" id="tciss-filters">
                <div class="col-lg-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Search household head, address, reference"></div>
                <div class="col-lg-3"><select name="barangay_id" class="form-select form-select-solid"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></div>
                <div class="col-lg-3"><select name="evacuation_center_id" class="form-select form-select-solid"><option value="">All evacuation centers</option>@foreach ($evacuationCenters as $center)<option value="{{ $center->id }}" @selected(request('evacuation_center_id') == $center->id)>{{ $center->name }}</option>@endforeach</select></div>
                <div class="col-lg-2"><button class="btn btn-light w-100">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead><tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase"><th>Reference</th><th>Household Head</th><th>Barangay</th><th>Evacuation Center</th><th>Housing</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                    <tbody class="fw-semibold text-gray-800">
                        @forelse ($records as $record)
                            <tr>
                                <td>{{ $record->source_reference ?: 'No data available' }}</td>
                                <td><div class="d-flex flex-column"><span class="fw-bold">{{ $record->household_head_full_name }}</span><span class="text-muted fs-7">{{ $record->address }}</span></div></td>
                                <td>{{ $record->barangay?->name ?: 'No data available' }}</td>
                                <td>{{ $record->evacuationCenter?->name ?: 'No data available' }}</td>
                                <td>{{ $record->affectedFamily?->housing_condition ?: 'No data available' }}</td>
                                <td><span class="badge badge-light-{{ $record->verification_status === 'Verified' ? 'success' : 'warning' }}">{{ $record->verification_status }}</span></td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-light-primary js-open-tciss" data-details-url="{{ route('disaster.tciss.full-details', $record) }}">Open</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-10">No records to show yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-6">{{ $records->links() }}</div>
        </div>
    </div>
@endsection

@push('modals')
<div class="modal fade" id="tcissDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><div><h2 class="fw-bold mb-1">TCISS / DAFAC Details</h2><div id="details-reference" class="text-muted"></div></div><button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i></button></div>
            <div class="modal-body min-h-400px">
                <div id="details-loading" class="text-center py-20"><span class="spinner-border text-primary"></span><div class="mt-4 text-muted">Loading complete DAFAC details...</div></div>
                <div id="details-error" class="alert alert-danger d-none"></div>
                <div id="details-content" class="d-none">
                    <div id="summary-badges" class="d-flex flex-wrap gap-2 mb-6"></div>
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-7 fs-6">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#details-personal">Personal Details</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-family">Family Composition <span id="member-count" class="badge badge-light ms-1">0</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-validation">Validation</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-attachments">Attachments</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-assistance">Assistance History</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="details-personal"><div id="summary-section"></div><div id="disaster-section"></div><div id="personal-section"></div><div id="housing-section"></div></div>
                        <div class="tab-pane fade" id="details-family"><div class="table-responsive"><table class="table table-row-dashed align-middle gy-4"><thead><tr class="fw-bold text-muted fs-7 text-uppercase"><th>No.</th><th>Full Name</th><th>Birthdate</th><th>Age</th><th>Relationship</th><th>Sex</th><th>Occupation</th><th>Monthly Income</th><th>Health</th><th>Remarks</th><th>Description</th></tr></thead><tbody id="family-members"></tbody></table></div></div>
                        <div class="tab-pane fade" id="details-validation"><div id="validation-section"></div><div id="duplicate-section"></div></div>
                        <div class="tab-pane fade" id="details-attachments"><div id="attachments-section" class="row g-5"></div></div>
                        <div class="tab-pane fade" id="details-assistance"><div id="assistance-section"></div><div id="payout-section"></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const modalElement = document.getElementById('tcissDetailsModal');
    const modal = new bootstrap.Modal(modalElement);
    const empty = value => value === null || value === undefined || value === '' ? 'No data available' : value;
    const esc = value => $('<div>').text(empty(value)).html();
    const date = value => value ? new Intl.DateTimeFormat('en-PH', {year:'numeric', month:'short', day:'numeric'}).format(new Date(value)) : 'No data available';
    const money = value => value !== null && value !== undefined ? new Intl.NumberFormat('en-PH', {style:'currency', currency:'PHP'}).format(value) : 'No data available';
    const badge = value => `<span class="badge badge-light-primary">${esc(value)}</span>`;
    const card = (title, fields) => `<div class="card card-bordered mb-6"><div class="card-header min-h-50px"><h3 class="card-title fs-5">${title}</h3></div><div class="card-body py-5"><div class="row g-5">${fields.map(([label,value]) => `<div class="col-md-4"><div class="text-muted fs-7 mb-1">${label}</div><div class="fw-semibold">${value}</div></div>`).join('')}</div></div></div>`;
    const remarks = {A:'Elderly', B:'Person with Disabilities', C:'Infant', D:'Pregnant Woman', E:'Lactating Mother', F:'Child'};
    const emptyRow = columns => `<tr><td colspan="${columns}" class="text-center text-muted py-10">No data available</td></tr>`;

    document.querySelectorAll('.js-open-tciss').forEach(button => button.addEventListener('click', async () => {
        sessionStorage.setItem('tcissScrollPosition', window.scrollY);
        modal.show();
        $('#details-loading').removeClass('d-none'); $('#details-error, #details-content').addClass('d-none');
        try {
            const response = await fetch(button.dataset.detailsUrl, {headers:{Accept:'application/json', 'X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) throw new Error(response.status === 403 ? 'You are not authorized to view this record.' : response.status === 404 ? 'The selected record could not be found.' : 'The record could not be loaded. Please try again.');
            const {data:d} = await response.json(), f = d.affected_family, df = d.dafac, v = d.validation;
            $('#details-reference').text(`${empty(d.masterlist.reference_number)} • ${empty(df?.reference_number)}`);
            $('#summary-badges').html([f?.status, d.masterlist.verification_status, d.duplicate_checks[0]?.resolution_status].filter(Boolean).map(badge).join(''));
            $('#summary-section').html(card('Record Summary', [['TCISS Reference',esc(d.masterlist.reference_number)],['DAFAC Reference',esc(df?.reference_number)],['Workflow Status',esc(f?.status)],['Verification Status',esc(d.masterlist.verification_status)],['Duplicate Check Status',esc(d.duplicate_checks[0]?.resolution_status)],['Date Created',date(d.masterlist.created_at)],['Last Updated',date(d.masterlist.updated_at)]]));
            $('#disaster-section').html(card('Disaster and Location Information', [['Disaster / Incident',esc(f?.disaster_name)],['Type of Disaster',esc(f?.disaster_type)],['Incident Date',date(f?.incident_date)],['Barangay',esc(f?.barangay)],['Evacuation Center',esc(f?.evacuation_center)],['Complete Address',esc(f?.complete_address)],['DAFAC Interview Date',date(df?.interview_date)]]));
            $('#personal-section').html(card('Household Head Information', [['Surname',esc(f?.surname)],['Given Name',esc(f?.given_name)],['Middle Name',esc(f?.middle_name)],['Full Name',esc(f?.full_name)],['Birthdate',date(f?.birthdate)],['Calculated Age',esc(f?.age)],['Sex',esc(f?.sex)],['Occupation',esc(f?.occupation)],['Monthly Income',money(f?.monthly_income)],['Contact Number',esc(f?.contact_number)]]));
            $('#housing-section').html(card('Household and Housing Information', [['House Ownership',esc(f?.house_ownership)],['Housing Condition',esc(f?.housing_condition)],['Health Condition',esc(f?.health_condition)],['Total Family Members',esc(f?.member_count)],['Interviewed By',esc(df?.interviewed_by)],['Validated By',esc(v?.validated_by || df?.validated_by)],['Validation Date',date(v?.validated_at)],['Validation Notes',esc(v?.notes)]]));
            $('#member-count').text(d.family_members.length);
            $('#family-members').html(d.family_members.length ? d.family_members.map((m,i) => `<tr><td>${i+1}</td><td class="fw-bold">${esc(m.name)}</td><td>${date(m.birthdate)}</td><td>${esc(m.age)}</td><td>${esc(m.relationship)}</td><td>${esc(m.sex)}</td><td>${esc(m.occupation)}</td><td>${money(m.monthly_income)}</td><td>${badge(m.health_condition)}</td><td>${badge(m.remarks_code)}</td><td>${esc(remarks[m.remarks_code])}</td></tr>`).join('') : emptyRow(11));
            $('#validation-section').html(card('Validation Information', [['Status',esc(v?.status)],['Validated Ownership',esc(v?.house_ownership)],['Validated Housing',esc(v?.housing_condition)],['Validated By',esc(v?.validated_by)],['Validation Date',date(v?.validated_at)],['Notes',esc(v?.notes)]]));
            $('#duplicate-section').html(d.duplicate_checks.length ? d.duplicate_checks.map(x => card('Duplicate Check Information', [['Possible Duplicate',x.possible_duplicate_found?'Yes':'No'],['Match Score',esc(x.match_score)],['Match Reason',esc(x.match_reason)],['Matched Household',esc(x.matched_household)],['Resolution',esc(x.resolution_status)],['Resolved By',esc(x.resolved_by)],['Resolution Date',date(x.resolved_at)]])).join('') : card('Duplicate Check Information', [['Status','No data available']]));
            $('#attachments-section').html(d.attachments.length ? d.attachments.map(a => `<div class="col-md-4"><div class="card card-bordered h-100"><div class="card-body"><div class="fw-bold mb-2">${esc(a.type)}</div><div class="text-muted fs-7 mb-4">${esc(a.name)}</div><a href="${a.url}" class="btn btn-sm btn-light-primary" target="_blank" rel="noopener">View file</a></div></div></div>`).join('') : '<div class="col-12 text-center text-muted py-10">No data available</div>');
            $('#assistance-section').html(d.assistance_history.length ? d.assistance_history.map(a => card('Assistance', [['Date',date(a.date)],['Kind',esc(a.kind)],['Quantity / Amount',money(a.quantity_amount)],['Provider',esc(a.provider)],['Released By',esc(a.released_by)]])).join('') : card('Assistance History', [['Status','No data available']]));
            $('#payout-section').html(d.payout.length ? d.payout.map(p => card('Payout', [['Schedule',esc(p.schedule)],['Scheduled Date',date(p.scheduled_date)],['Status',esc(p.status)],['Released By',esc(p.released_by)],['Released Date',date(p.released_at)]])).join('') : card('Payout History', [['Status','No data available']]));
            $('#details-loading').addClass('d-none'); $('#details-content').removeClass('d-none');
        } catch (error) { $('#details-loading').addClass('d-none'); $('#details-error').text(error.message).removeClass('d-none'); }
    }));
    modalElement.addEventListener('hidden.bs.modal', () => window.scrollTo(0, Number(sessionStorage.getItem('tcissScrollPosition') || 0)));
})();
</script>
@endpush
