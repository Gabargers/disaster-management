@extends('layouts.dashboard.main')

@section('content')
    <div class="card card-flush shadow-sm mb-8">
        <div class="card-header align-items-center">
            <div class="card-title"><div><h3 class="fw-bold mb-1">TCISS / Masterlist Verification</h3><div class="text-muted fs-7">Review DAFAC-linked households, verification, location, and payout progress.</div></div></div>
            <div class="card-toolbar gap-2"><span class="badge badge-light-primary fs-7">{{$records->total()}} {{Str::plural('record',$records->total())}}</span>@if(request()->query())<a href="{{route('disaster.tciss.index')}}" class="btn btn-sm btn-light-danger">Clear filters</a>@endif</div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-4 mb-8 align-items-end" id="tciss-filters">
                <div class="col-md-6 col-xl-4"><label class="form-label">Search</label><div class="input-group"><span class="input-group-text border-0"><i class="ki-duotone ki-magnifier fs-3"><span class="path1"></span><span class="path2"></span></i></span><input name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Household, address, TCISS or DAFAC"></div></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Barangay</label><select name="barangay_id" class="form-select form-select-solid"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Evacuation Center</label><select name="evacuation_center_id" class="form-select form-select-solid"><option value="">All centers</option>@foreach ($evacuationCenters as $center)<option value="{{ $center->id }}" @selected(request('evacuation_center_id') == $center->id)>{{ $center->name }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Disaster</label><select name="disaster_id" class="form-select form-select-solid"><option value="">All disasters</option>@foreach($disasters as $disaster)<option value="{{$disaster->id}}" @selected(request('disaster_id')==$disaster->id)>{{$disaster->name}}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Verification</label><select name="verification_status" class="form-select form-select-solid"><option value="">All verification</option>@foreach(['Needs Review','Verified'] as $status)<option @selected(request('verification_status')===$status)>{{$status}}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-3"><label class="form-label">Workflow Status</label><select name="workflow_status" class="form-select form-select-solid"><option value="">All workflow statuses</option>@foreach(\App\Enums\FamilyStatus::cases() as $status)<option value="{{$status->value}}" @selected(request('workflow_status')===$status->value)>{{str_replace('_',' ',$status->value)}}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Payout Status</label><select name="payout_status" class="form-select form-select-solid"><option value="">All payouts</option>@foreach(['Pending','Scheduled','Released','Cancelled'] as $status)<option @selected(request('payout_status')===$status)>{{$status}}</option>@endforeach</select></div>
                <div class="col-6 col-xl-2"><label class="form-label">Created From</label><input type="date" name="date_from" value="{{request('date_from')}}" class="form-control form-control-solid"></div><div class="col-6 col-xl-2"><label class="form-label">Created To</label><input type="date" name="date_to" value="{{request('date_to')}}" class="form-control form-control-solid"></div>
                <div class="col-xl-3"><button class="btn btn-primary w-100"><i class="ki-duotone ki-filter fs-3"><span class="path1"></span><span class="path2"></span></i>Apply Filters</button></div>
            </form>

            <div class="table-responsive tciss-table-wrap">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead><tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase"><th>TCISS / DAFAC</th><th>Household Head</th><th>Barangay / Center</th><th>Members</th><th>Disaster</th><th>Verification / Workflow</th><th>Payout</th><th>Created</th><th class="text-end">Action</th></tr></thead>
                    <tbody class="fw-semibold text-gray-800">
                        @forelse ($records as $record)
                            <tr>
                                <td class="text-nowrap"><strong>{{ $record->source_reference ?: '—' }}</strong><div class="text-muted fs-7">{{ $record->dafacRecord?->reference_number ?: '—' }}</div><div class="text-muted fs-8">{{str_replace('_',' ',$record->source)}}</div></td>
                                <td><div class="d-flex flex-column tciss-household"><span class="fw-bold text-gray-900">{{ $record->household_head_full_name }}</span><span class="text-muted fs-7 text-truncate">{{ $record->address }}</span></div></td>
                                <td>{{ $record->barangay?->name ?: '—' }}<div class="text-muted fs-7 tciss-location js-center-name">{{ $record->evacuationCenter?->name ?: 'Unassigned' }}</div></td>
                                <td>{{$record->affectedFamily?->familyMembers->count()??0}} additional<div class="text-muted fs-7">{{($record->affectedFamily?->familyMembers->count()??0)+1}} total</div></td>
                                <td>{{$record->affectedFamily?->disaster?->type?:'—'}}</td>
                                <td><span class="badge badge-light-{{ $record->verification_status === 'Verified' ? 'success' : 'warning' }}">{{ $record->verification_status }}</span><div class="text-muted fs-7 mt-1">{{str_replace('_',' ',$record->affectedFamily?->status?->value??'—')}}</div></td>
                                @php($latestPayout=$record->affectedFamily?->payoutReleases->sortByDesc('id')->first())<td><span class="badge badge-light-{{$latestPayout?->status==='Released'?'success':($latestPayout?'warning':'secondary')}}">{{$latestPayout?->status??'Not Ready'}}</span></td><td class="text-nowrap">{{$record->created_at?->format('M d, Y')}}</td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-light-primary js-open-tciss" data-details-url="{{ route('disaster.tciss.full-details', $record) }}">Open</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><div class="text-center py-12"><i class="ki-duotone ki-people fs-3x text-muted"><span class="path1"></span><span class="path2"></span></i><h4 class="mt-4 mb-1">No matching masterlist records</h4><div class="text-muted">Adjust or clear the filters and try again.</div></div></td></tr>
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
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-7 fs-6 flex-nowrap overflow-auto tciss-tabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#details-personal">Personal Details</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-family">Family Composition <span id="member-count" class="badge badge-light ms-1">0</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-validation">Validation</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-assignment">Evacuation Center</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-attachments">Attachments</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#details-assistance">Assistance History</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="details-personal"><div id="summary-section"></div><div id="disaster-section"></div><div id="personal-section"></div><div id="housing-section"></div></div>
                        <div class="tab-pane fade" id="details-family"><div class="table-responsive"><table class="table table-row-dashed align-middle gy-4"><thead><tr class="fw-bold text-muted fs-7 text-uppercase"><th>No.</th><th>Full Name</th><th>Birthdate</th><th>Age</th><th>Relationship</th><th>Sex</th><th>Occupation</th><th>Monthly Income</th><th>Health</th><th>Remarks</th><th>Description</th></tr></thead><tbody id="family-members"></tbody></table></div></div>
                        <div class="tab-pane fade" id="details-validation"><div id="validation-section"></div><div id="duplicate-section"></div></div>
                        <div class="tab-pane fade" id="details-assignment"><div id="assignment-section"></div></div>
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

@push('styles')<style>
.tciss-household{min-width:210px;max-width:300px}.tciss-location{min-width:150px;max-width:220px}.tciss-table-wrap{min-height:280px}.tciss-tabs .nav-link{white-space:nowrap}.tciss-table-wrap th{white-space:nowrap}@media(max-width:767px){#tciss-filters .form-label{margin-bottom:.35rem}.card-header{gap:.75rem}.tciss-household{max-width:230px}}
</style>@endpush

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
    const centerOptions = (centers,currentId) => '<option value="">Select evacuation center</option>'+centers.filter(c=>c.id!==currentId).map(c=>`<option value="${c.id}" data-center='${JSON.stringify(c).replace(/'/g,'&#39;')}' ${c.is_full&&!c.can_override?'disabled':''}>${esc(c.name)} — ${c.occupied_count} / ${c.capacity} occupied, ${c.available_slots} available${c.is_full&&!c.can_override?' (Full)':''}</option>`).join('');
    const assignmentCard = (a, trigger) => {
        const current=a.current, history=a.history||[], barangays=(a.barangays||[]).map(b=>`<option value="${b.id}" ${b.id===a.barangay?.id?'selected':''}>${esc(b.name)}</option>`).join('');
        const previous=history.find(h=>h.status!=='ACTIVE');
        const currentHtml=current?`<div class="row g-4"><div class="col-md-3"><div class="text-muted fs-7">Current Barangay</div><div>${esc(current.barangay)}</div></div><div class="col-md-3"><div class="text-muted fs-7">Current Evacuation Center</div><div class="fw-bold">${esc(current.center)}</div></div><div class="col-md-2"><div class="text-muted fs-7">Status</div><span class="badge badge-light-success">${esc(current.status)}</span></div><div class="col-md-2"><div class="text-muted fs-7">Assigned</div><div>${date(current.assigned_at)}</div></div><div class="col-md-2"><div class="text-muted fs-7">Assigned By</div><div>${esc(current.assigned_by)}</div></div><div class="col-md-4"><div class="text-muted fs-7">Disaster</div><div>${esc(a.disaster?.name)}</div></div><div class="col-md-4"><div class="text-muted fs-7">Previous Center</div><div>${esc(previous?.center)}</div></div><div class="col-md-4"><div class="text-muted fs-7">Transfer Reason</div><div>${esc(previous?.transfer_reason)}</div></div></div>`:'<div class="notice bg-light-warning border border-warning border-dashed rounded p-4">No evacuation center is currently assigned to this family.</div>';
        const form=a.can_assign?`<form id="assignment-form" data-url="${a.assign_url}" data-current-center="${current?.center_id||''}" data-current-name="${esc(current?.center||'')}" data-household="${esc(a.household_head)}" data-disaster="${a.disaster?.id}" class="mt-6"><div id="assignment-error" class="alert alert-danger d-none"></div><div class="row g-4 align-items-end"><div class="col-md-4"><label class="form-label required">Barangay</label><select name="barangay_id" class="form-select form-select-solid" required>${barangays}</select></div><div class="col-md-5"><label class="form-label required">Evacuation Center</label><select name="evacuation_center_id" class="form-select form-select-solid" required>${centerOptions(a.centers||[],current?.center_id)}</select></div><div class="col-md-3"><label class="form-label required">Assignment Date</label><input name="assignment_date" type="date" max="${new Date().toISOString().slice(0,10)}" value="${new Date().toISOString().slice(0,10)}" class="form-control form-control-solid" required></div><div class="col-md-6"><label class="form-label">Assignment Notes</label><input name="notes" class="form-control form-control-solid" placeholder="Optional assignment notes"></div><div class="col-md-6 ${current?'':'d-none'}" id="transfer-reason-field"><label class="form-label required">Transfer Reason</label><input name="transfer_reason" class="form-control form-control-solid" placeholder="Required when changing the current center"></div><div class="col-12"><div id="capacity-preview" class="d-none mb-4"></div><button type="button" class="btn btn-light me-2 js-cancel-assignment">Cancel</button><button class="btn btn-primary"><span class="indicator-label">${current?'Transfer Evacuation Center':'Assign Evacuation Center'}</span><span class="indicator-progress">Saving... <span class="spinner-border spinner-border-sm ms-2"></span></span></button></div></div></form>`:'<div class="notice bg-light rounded border border-dashed p-4 mt-5">You have read-only access to this assignment.</div>';
        const rows=history.length?history.map(h=>`<tr><td>${esc(h.center)}</td><td>${esc(h.barangay)}</td><td><span class="badge badge-light-${h.status==='ACTIVE'?'success':'secondary'}">${esc(h.status)}</span></td><td>${date(h.assigned_at)}</td><td>${date(h.unassigned_at)}</td><td>${esc(h.assigned_by)}</td><td>${esc(h.transfer_reason)}</td></tr>`).join(''):emptyRow(7);
        $('#assignment-section').html(`<div class="card card-bordered"><div class="card-header min-h-50px"><h3 class="card-title fs-5">Evacuation Center Assignment</h3></div><div class="card-body"><h5 class="mb-4">Current Assignment</h5>${currentHtml}${form}<div class="separator my-7"></div><h5>Assignment History</h5><div class="table-responsive"><table class="table table-row-dashed align-middle gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Evacuation Center</th><th>Barangay</th><th>Status</th><th>Assigned At</th><th>Unassigned At</th><th>Assigned By</th><th>Transfer Reason</th></tr></thead><tbody>${rows}</tbody></table></div></div></div>`).data('trigger',trigger);
        if(!a.can_assign) $('#assignment-section .notice').last().text(a.lock_reason||'You do not have permission to modify this assignment.');
    };

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
            assignmentCard(d.assignment, button);
            $('#attachments-section').html(d.attachments.length ? d.attachments.map(a => `<div class="col-md-4"><div class="card card-bordered h-100"><div class="card-body"><div class="fw-bold mb-2">${esc(a.type)}</div><div class="text-muted fs-7 mb-4">${esc(a.name)}</div><a href="${a.url}" class="btn btn-sm btn-light-primary" target="_blank" rel="noopener">View file</a></div></div></div>`).join('') : '<div class="col-12 text-center text-muted py-10">No data available</div>');
            $('#assistance-section').html(d.assistance_history.length ? d.assistance_history.map(a => card('Assistance', [['Date',date(a.date)],['Kind',esc(a.kind)],['Quantity / Amount',money(a.quantity_amount)],['Provider',esc(a.provider)],['Released By',esc(a.released_by)]])).join('') : card('Assistance History', [['Status','No data available']]));
            $('#payout-section').html(d.payout.length ? d.payout.map(p => card('Payout', [['Schedule',esc(p.schedule)],['Scheduled Date',date(p.scheduled_date)],['Status',esc(p.status)],['Released By',esc(p.released_by)],['Released Date',date(p.released_at)]])).join('') : card('Payout History', [['Status','No data available']]));
            $('#details-loading').addClass('d-none'); $('#details-content').removeClass('d-none');
        } catch (error) { $('#details-loading').addClass('d-none'); $('#details-error').text(error.message).removeClass('d-none'); }
    }));
    modalElement.addEventListener('change',async event=>{const form=event.target.closest('#assignment-form');if(!form)return;if(event.target.name==='barangay_id'){const select=form.elements.evacuation_center_id;select.disabled=true;select.innerHTML='<option>Loading evacuation centers...</option>';$('#capacity-preview').addClass('d-none');try{const response=await fetch(`{{url('/disaster/barangays')}}/${event.target.value}/evacuation-centers?disaster_id=${form.dataset.disaster}`,{headers:{Accept:'application/json'}}),payload=await response.json();if(!response.ok)throw new Error(payload.message||'Centers could not be loaded.');select.innerHTML=centerOptions(payload.data,Number(form.dataset.currentCenter)||null);select.disabled=false}catch(error){select.innerHTML='<option value="">No centers available</option>';$('#assignment-error').text(error.message).removeClass('d-none')}}if(event.target.name==='evacuation_center_id'){const option=event.target.selectedOptions[0],center=option?.dataset.center?JSON.parse(option.dataset.center):null;if(!center)return $('#capacity-preview').addClass('d-none');const percent=center.capacity?Math.min(100,(center.occupied_count/center.capacity)*100):0,tone=center.available_slots<=5?'danger':(percent>=75?'warning':'success');$('#capacity-preview').html(`<div class="notice bg-light-${tone} rounded p-4"><div class="d-flex justify-content-between mb-2"><strong>${center.occupied_count} of ${center.capacity} occupied</strong><span>${center.available_slots} slots available</span></div><div class="progress h-6px"><div class="progress-bar bg-${tone}" style="width:${percent}%"></div></div><div class="text-muted fs-7 mt-2">${percent.toFixed(1)}% full</div></div>`).removeClass('d-none')}});
    modalElement.addEventListener('click',event=>{if(event.target.closest('.js-cancel-assignment')){const form=event.target.closest('form');form.reset();$('#capacity-preview,#assignment-error').addClass('d-none')}});
    modalElement.addEventListener('submit',async event=>{const form=event.target.closest('#assignment-form');if(!form)return;event.preventDefault();const trigger=$('#assignment-section').data('trigger'),selected=form.elements.evacuation_center_id.selectedOptions[0],newName=selected?.textContent?.split(' — ')[0],isTransfer=!!form.dataset.currentCenter&&Number(form.dataset.currentCenter)!==Number(form.elements.evacuation_center_id.value);if(isTransfer&&!form.elements.transfer_reason.value.trim()){form.elements.transfer_reason.classList.add('is-invalid');return $('#assignment-error').text('A transfer reason is required.').removeClass('d-none')}if(isTransfer){const answer=await Swal.fire({title:'Confirm Evacuation Center Transfer',html:`<div class="text-start"><p>This family is currently assigned to <strong>${esc(form.dataset.currentName)}</strong>. Are you sure you want to transfer them to <strong>${esc(newName)}</strong>?</p><div><strong>Household Head:</strong> ${esc(form.dataset.household)}</div><div><strong>Transfer Reason:</strong> ${esc(form.elements.transfer_reason.value)}</div></div>`,icon:'warning',showCancelButton:true,confirmButtonText:'Confirm Transfer'});if(!answer.isConfirmed)return}const submit=form.querySelector('button[type="submit"],button.btn-primary');submit.disabled=true;submit.setAttribute('data-kt-indicator','on');$('#assignment-error').addClass('d-none');try{const response=await fetch(form.dataset.url,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:new FormData(form)}),payload=await response.json();if(!response.ok)throw new Error(payload.message||Object.values(payload.errors||{}).flat().join('\n')||'Assignment could not be saved.');assignmentCard(payload.data.assignment_view,trigger);trigger.closest('tr').querySelector('.js-center-name').textContent=payload.data.assignment_view.current.center;window.dispatchEvent(new CustomEvent('evacuation-assignment-updated',{detail:payload.data}));await Swal.fire({text:payload.message,icon:'success'})}catch(error){$('#assignment-error').text(error.message).removeClass('d-none');submit.disabled=false;submit.removeAttribute('data-kt-indicator')}});
    modalElement.addEventListener('hidden.bs.modal', () => window.scrollTo(0, Number(sessionStorage.getItem('tcissScrollPosition') || 0)));
})();
</script>
@endpush
