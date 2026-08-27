@extends('layouts.dashboard.main')

@section('content')
<div class="row g-5 mb-8">
    @foreach([['Families received',$totalPeople,'primary','profile-user'],['Assigned families',$totalAssigned,'success','abstract-26'],['Latest API update',$latestReceivedAt ? \Illuminate\Support\Carbon::parse($latestReceivedAt)->format('M d, Y h:i A') : 'No data yet','warning','time']] as [$label,$value,$color,$icon])
    <div class="col-md-4"><div class="card card-flush h-100 shadow-sm"><div class="card-body d-flex align-items-center gap-5"><span class="symbol symbol-55px"><span class="symbol-label bg-light-{{$color}}"><i class="ki-duotone ki-{{$icon}} fs-2x text-{{$color}}"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></span></span><div><div @if($label === 'Assigned families') id="assigned-families-count" @endif class="fs-{{is_numeric($value)?'2':'6'}} fw-bold text-gray-900">{{is_numeric($value)?number_format($value):$value}}</div><div class="text-muted fw-semibold">{{$label}}</div></div></div></div></div>
    @endforeach
</div>

<div class="card card-flush shadow-sm">
    <div class="card-header align-items-center py-5"><div class="card-title"><div><h3 class="fw-bold mb-1">Affected Families</h3><div class="text-muted fs-7">Family records received through the TCISS API.</div></div></div><div class="card-toolbar"><span class="badge badge-light-primary">{{$people->total()}} {{Str::plural('record',$people->total())}}</span></div></div>
    <div class="card-body pt-0">
        <form method="GET" class="row g-4 mb-8 align-items-end"><div class="col-md-6"><label class="form-label">Family member name or control number</label><input type="search" name="search" value="{{request('search')}}" class="form-control form-control-solid" placeholder="Search family head or member"></div><div class="col-md-3"><label class="form-label">Latest status</label><select name="status" class="form-select form-select-solid"><option value="">All statuses</option>@foreach($statuses as $option)<option value="{{$option}}" @selected(request('status')===$option)>{{Str::headline($option)}}</option>@endforeach</select></div><div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button>@if(request()->query())<a href="{{route('disaster.person-affecteds.index')}}" class="btn btn-light">Clear</a>@endif</div></form>
        <div class="table-responsive"><table class="table align-middle table-row-dashed gy-5"><thead><tr class="text-gray-600 fw-bold fs-7 text-uppercase"><th>Control Number</th><th>Family Head</th><th>Barangay</th><th>Street</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
        @forelse($people as $person)@php($matchedMember = $person->matched_family_member)@php($detailsUrl = route('disaster.person-affecteds.show', $matchedMember ? [$person, 'member_control_number' => $matchedMember->control_number] : $person))<tr data-person-id="{{$person->id}}"><td><span class="text-primary fw-semibold">{{$matchedMember?->control_number ?? $person->control_number}}</span></td><td><span class="fw-bold text-gray-900">{{$matchedMember?->full_name ?? $person->full_name ?: '—'}}</span>@if($matchedMember)<div class="text-muted fs-7">Family head: {{$person->full_name}}</div>@endif</td><td>{{$person->barangay ?: '—'}}</td><td>{{$person->street ?: '—'}}</td><td class="js-assignment-status">@if($person->evacuation_center_id)<span class="badge badge-light-success" title="{{$person->evacuationCenter?->name}}">Assigned to Evacuation Center</span>@else<span class="badge badge-light-danger">{{Str::headline($person->latestStatus?->status ?? 'Unknown')}}</span>@endif</td><td class="text-end js-details-action">@if($person->evacuation_center_id)<button type="button" class="btn btn-sm btn-light-secondary" disabled title="Manage this family from {{$person->evacuationCenter?->name ?? 'the assigned evacuation center'}}">Details</button>@else<button type="button" class="btn btn-sm btn-light-primary js-person-details" data-bs-toggle="modal" data-bs-target="#residentModal" data-url="{{$detailsUrl}}">Details</button>@endif</td></tr>
        @empty<tr><td colspan="6"><div class="text-center py-15"><i class="ki-duotone ki-profile-user fs-3x text-muted"><span class="path1"></span><span class="path2"></span></i><h4 class="mt-4">No affected family records found</h4><p class="text-muted">Records sent by the TCISS API will appear here automatically.</p></div></td></tr>@endforelse
        </tbody></table></div><div class="d-flex justify-content-end mt-5">{{$people->links()}}</div>
    </div>
</div>

<div class="modal fade" id="residentModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><div class="text-muted text-uppercase fs-8 fw-bold">Family Record</div><h2 id="resident-name" class="mb-1">Family Head</h2><div id="resident-control" class="text-primary fw-semibold"></div></div><button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i></button></div><div class="modal-body"><div id="resident-loading" class="text-center py-15"><span class="spinner-border text-primary"></span></div><div id="resident-content" class="d-none"><div class="row g-5 mb-5"><div class="col-lg-4"><div class="border rounded p-6 h-100"><div class="d-flex justify-content-between align-items-center mb-4"><h3 class="mb-0">Verification Image</h3><span class="badge badge-light-primary">View only</span></div><div id="tciss-image-frame" class="tciss-image-frame"><img id="tciss-verification-image" class="d-none" alt="TCISS verification image"><div id="tciss-image-placeholder" class="text-center text-muted px-5"><i class="ki-duotone ki-picture fs-3x"><span class="path1"></span><span class="path2"></span></i><div class="fw-semibold mt-3">Image not yet available</div><div class="fs-7 mt-1">The TCISS verification image will appear here once the image service is connected.</div></div></div><div id="tciss-image-note" class="text-muted fs-8 mt-3">For verification and viewing only.</div></div></div><div class="col-lg-8"><div class="border rounded p-6 h-100"><h3>Family Head Information</h3><div id="personal-info" class="detail-grid"></div></div></div><div class="col-lg-6"><div class="border rounded p-6 h-100"><h3>Address</h3><div id="address-info" class="detail-grid"></div></div></div><div class="col-lg-6"><div class="border rounded p-6 h-100"><h3>Family Information</h3><div id="family-info" class="detail-grid"></div></div></div></div><div class="border rounded p-6 mb-5"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h3 class="mb-1">Evacuation Center Assignment</h3><div id="current-center" class="text-muted fs-7">Not assigned</div></div></div><div id="assignment-message"></div><form id="person-center-form" class="row g-3 align-items-end"><div class="col-md-9"><label class="form-label">Evacuation Center</label><select id="person-center-select" name="evacuation_center_id" class="form-select form-select-solid"></select></div><div class="col-md-3"><button id="assign-center-button" type="submit" class="btn btn-primary w-100">Assign Evacuation Center</button></div></form></div><div class="border rounded p-6"><div class="d-flex justify-content-between"><h3>Family Composition</h3><span id="family-count" class="badge badge-light-primary"></span></div><div class="table-responsive"><table class="table table-row-dashed gy-4"><thead><tr><th>Control No.</th><th>Name</th><th>Relationship</th><th>Age</th><th>Sex</th><th>Code</th><th>Housing</th><th class="text-end">Action</th></tr></thead><tbody id="family-rows"></tbody></table></div></div></div></div></div></div></div>

<div class="modal fade" id="familyMemberModal" tabindex="-1" aria-labelledby="family-member-name" aria-hidden="true"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><div class="text-muted text-uppercase fs-8 fw-bold">Family Member</div><h3 id="family-member-name" class="mb-0">Member Details</h3></div><button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i></button></div><div class="modal-body"><div id="family-member-details" class="detail-grid"></div></div></div></div></div>
@endsection

@push('styles')
<style>.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem 2rem}.detail-item.wide{grid-column:1/-1}.detail-label{color:#99a1b7;font-size:.78rem;margin-bottom:.15rem}.detail-value{color:#071437;font-weight:500}.tciss-image-frame{min-height:280px;border:2px dashed var(--bs-gray-300);border-radius:.75rem;background:var(--bs-gray-100);display:grid;place-items:center;overflow:hidden}.tciss-image-frame img{width:100%;height:280px;object-fit:contain;background:#fff}.family-member-backdrop.show{opacity:.78}@media(max-width:575px){.detail-grid{grid-template-columns:1fr}}</style>
@endpush

@push('scripts')
<script>
let currentFamilyMembers = [];

document.getElementById('residentModal').addEventListener('show.bs.modal', async event => {
    const button = event.relatedTarget;
    if (!button?.dataset.url) return;

    const element = id => document.getElementById(id);
    const escapeHtml = value => String(value ?? '—').replace(/[&<>'"]/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    })[character]);
    const grid = (id, items) => element(id).innerHTML = items.map(([label, value, wide]) =>
        `<div class="detail-item ${wide ? 'wide' : ''}"><div class="detail-label">${escapeHtml(label)}</div><div class="detail-value">${escapeHtml(value)}</div></div>`
    ).join('');

    element('resident-name').textContent = 'Family Head';
    element('resident-control').textContent = '';
    element('resident-loading').innerHTML = '<span class="spinner-border text-primary"></span>';
    element('resident-loading').classList.remove('d-none');
    element('resident-content').classList.add('d-none');
    element('tciss-verification-image').removeAttribute('src');
    element('tciss-verification-image').classList.add('d-none');
    element('tciss-image-placeholder').classList.remove('d-none');

    try {
        const response = await fetch(button.dataset.url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('Unable to load family record.');

        const data = (await response.json()).data;
        element('resident-name').textContent = data.full_name || 'Family Head';
        element('resident-control').textContent = data.control_number;
        if (data.tciss_image?.available && data.tciss_image.url) {
            const verificationImage = element('tciss-verification-image');
            verificationImage.src = data.tciss_image.url;
            verificationImage.alt = data.tciss_image.label || 'TCISS verification image';
            verificationImage.classList.remove('d-none');
            element('tciss-image-placeholder').classList.add('d-none');
        }
        grid('personal-info', [['Birthday', data.birthdate], ['Age', data.age], ['Sex', data.sex], ['Code', data.code], ['Occupation', data.occupation, true], ['Monthly Income', data.monthly_income, true], ['Health Condition', data.health_condition, true]]);
        grid('address-info', [['District', data.district], ['Barangay', data.barangay], ['Street', data.street, true], ['City', data.city, true]]);
        grid('family-info', [['Family Head', data.family_head_name, true], ['Family Head CN', data.family_head_control_number, true], ['Relationship', data.relationship], ['Housing', data.housing]]);
        const assignment = data.evacuation_center_assignment;
        const centerSelect = element('person-center-select');
        const assignButton = element('assign-center-button');
        const assignmentMessage = element('assignment-message');
        assignmentMessage.className = '';
        element('person-center-form').dataset.url = assignment.assign_url;
        element('current-center').textContent = data.evacuation_center
            ? `Currently assigned to ${data.evacuation_center.name}${data.evacuation_center.barangay ? ` — ${data.evacuation_center.barangay}` : ''}`
            : 'Not assigned to an evacuation center.';
        centerSelect.innerHTML = assignment.centers.map(center =>
            `<option value="${center.id}" ${data.evacuation_center?.id === center.id ? 'selected' : ''} ${center.is_full && data.evacuation_center?.id !== center.id ? 'disabled' : ''}>${escapeHtml(center.name)} — ${escapeHtml(center.barangay)} (${center.occupied}/${center.capacity})${center.is_full ? ' FULL' : ''}</option>`
        ).join('');
        centerSelect.disabled = !assignment.can_assign;
        assignButton.disabled = !assignment.can_assign;
        assignmentMessage.innerHTML = assignment.has_centers
            ? ''
            : `<div class="alert alert-warning">Create an active Evacuation Center first before assigning this family.${assignment.create_url ? ` <a href="${escapeHtml(assignment.create_url)}" class="alert-link">Go to Evacuation Center</a>` : ''}</div>`;
        currentFamilyMembers = data.family_members;
        element('family-count').textContent = `${data.family_members.length} members`;
        element('family-rows').innerHTML = data.family_members.length
            ? data.family_members.map((member, index) => `<tr><td>${escapeHtml(member.control_number)}</td><td>${escapeHtml(member.full_name)}</td><td>${escapeHtml(member.relationship)}</td><td>${escapeHtml(member.age)}</td><td>${escapeHtml(member.sex)}</td><td>${escapeHtml(member.code)}</td><td>${escapeHtml(member.housing)}</td><td class="text-end"><button type="button" class="btn btn-sm btn-light-primary js-family-member-details" data-member-index="${index}">Details</button></td></tr>`).join('')
            : '<tr><td colspan="8" class="text-center text-muted py-8">No family composition received.</td></tr>';
        element('resident-loading').classList.add('d-none');
        element('resident-content').classList.remove('d-none');
    } catch (error) {
        element('resident-loading').innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
    }
});

document.getElementById('family-rows').addEventListener('click', event => {
    const button = event.target.closest('.js-family-member-details');
    if (!button) return;

    const member = currentFamilyMembers[Number(button.dataset.memberIndex)];
    if (!member) return;

    const display = value => value === null || value === undefined || value === '' ? '—' : value;
    document.getElementById('family-member-name').textContent = display(member.full_name);
    document.getElementById('family-member-details').innerHTML = [
        ['Control Number', member.control_number, true],
        ['Relationship', member.relationship],
        ['Age', member.age],
        ['Sex', member.sex],
        ['Code', member.code],
        ['Housing', member.housing, true],
        ['Street', member.street, true],
        ['Barangay', member.barangay],
        ['District', member.district],
        ['City', member.city, true],
    ].map(([label, value, wide]) => `<div class="detail-item ${wide ? 'wide' : ''}"><div class="detail-label">${label}</div><div class="detail-value"></div></div>`).join('');

    document.querySelectorAll('#family-member-details .detail-value').forEach((element, index) => {
        element.textContent = display([
            member.control_number, member.relationship, member.age,
            member.sex, member.code, member.housing, member.street,
            member.barangay, member.district, member.city,
        ][index]);
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('familyMemberModal')).show();
});

document.getElementById('familyMemberModal').addEventListener('shown.bs.modal', () => {
    document.querySelectorAll('.modal-backdrop').item(document.querySelectorAll('.modal-backdrop').length - 1)
        ?.classList.add('family-member-backdrop');
});

document.getElementById('person-center-form').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = document.getElementById('assign-center-button');
    const message = document.getElementById('assignment-message');
    button.disabled = true;
    message.className = '';
    message.innerHTML = '';

    try {
        const response = await fetch(form.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: new FormData(form)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat().join(' '));
        const row = document.querySelector(`tr[data-person-id="${result.data.person_affected_id}"]`);
        if (row) {
            row.querySelector('.js-assignment-status').innerHTML = `<span class="badge badge-light-success" title="${result.data.center.name}">Assigned to Evacuation Center</span>`;
            row.querySelector('.js-details-action').innerHTML = `<button type="button" class="btn btn-sm btn-light-secondary" disabled title="Manage this family from ${result.data.center.name}">Details</button>`;
        }
        const assignedCount = document.getElementById('assigned-families-count');
        if (assignedCount) assignedCount.textContent = Number(result.data.assigned_families_count).toLocaleString();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('residentModal')).hide();
        await Swal.fire({
            text: `${result.message} ${result.data.center.families_count} ${result.data.center.families_count === 1 ? 'family is' : 'families are'} now assigned to ${result.data.center.name}.`,
            icon: 'success',
            timer: 1800,
            showConfirmButton: false,
        });
    } catch (error) {
        message.className = 'alert alert-danger';
        message.textContent = error.message;
        button.disabled = false;
    }
});
</script>
@endpush
