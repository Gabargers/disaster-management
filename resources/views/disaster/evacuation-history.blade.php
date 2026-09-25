@extends('layouts.dashboard.main')
@section('content')
<div class="card card-flush history-masterlist-card">
    <div class="card-header border-0 pt-6 align-items-center">
        <div class="card-title d-block">
            <h2 class="fw-bold text-gray-900 mb-1">Evacuation History</h2>
            <div class="text-muted fs-7">View the retained family masterlist and composition of a closed evacuation center.</div>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('disaster.payouts.index') }}" class="btn btn-light-primary fw-bold">
                <i class="ki-duotone ki-arrow-left fs-3"><span class="path1"></span><span class="path2"></span></i>Active Centers
            </a>
        </div>
    </div>

    <div class="card-body pt-4">
        @if($errors->any())
            <div class="alert alert-danger d-flex align-items-center mb-6"><i class="ki-duotone ki-information-5 fs-2x me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>{{ $errors->first() }}</div>
        @endif

        <form method="get" action="{{ route('disaster.payouts.history') }}" id="closedCenterSelector" class="history-selector mb-7">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end gap-4">
                <div class="flex-grow-1">
                    <label for="evacuation_center_id" class="form-label fw-bold text-gray-800">Closed Evacuation Center</label>
                    <div class="position-relative">
                        <i class="ki-duotone ki-geolocation fs-2 position-absolute top-50 translate-middle-y ms-4 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        <select id="evacuation_center_id" name="evacuation_center_id" class="form-select form-select-lg ps-12" @disabled($centerOptions->isEmpty())>
                            <option value="">Select a closed evacuation center</option>
                            @foreach($centerOptions as $center)
                                <option value="{{ $center->id }}" @selected($selectedCenter?->id === $center->id)>
                                    {{ $center->name }} — {{ $center->disaster_class_name ?: ($center->disaster?->name ?: 'No incident title') }} — {{ $center->closed_at?->format('M d, Y') ?: 'Date unavailable' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-muted fs-7 mt-2">Only closed evacuation centers are available in this list.</div>
                </div>
                @if($selectedCenter)
                    <a href="{{ route('disaster.payouts.history') }}" class="btn btn-light fw-bold">Clear Selection</a>
                @endif
            </div>
        </form>

        @if($selectedCenter)
            @php($individualCount = $familyRows->sum('household_size'))
            <div class="history-center-banner mb-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-5">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-2"><span class="badge badge-light-secondary">CLOSED</span><span class="text-muted fs-7">{{ $selectedCenter->disaster?->type ?: 'Disaster event' }}</span></div>
                        <h3 class="fw-bold text-gray-900 mb-1">{{ $selectedCenter->name }}</h3>
                        <div class="text-primary fw-semibold">{{ $selectedCenter->disaster_class_name ?: ($selectedCenter->disaster?->name ?: 'No incident title') }}</div>
                        <div class="text-muted fs-7 mt-2">{{ collect([$selectedCenter->address, $selectedCenter->barangay?->name])->filter()->implode(', ') ?: 'Address unavailable' }}</div>
                    </div>
                    <div class="history-center-stats">
                        <div><span>Assigned Families</span><strong>{{ number_format($familyRows->count()) }}</strong></div>
                        <div><span>Total Individuals</span><strong>{{ number_format($individualCount) }}</strong></div>
                        <div><span>Date Closed</span><strong class="fs-7">{{ $selectedCenter->closed_at?->format('M d, Y') ?: '—' }}</strong></div>
                        <div><span>Closed By</span><strong class="fs-7">{{ $selectedCenter->closedBy?->name ?: 'Unknown user' }}</strong></div>
                    </div>
                </div>
                @if($selectedCenter->closure_notes)<div class="history-closure-note mt-5"><span class="fw-bold">Closure note:</span> {{ $selectedCenter->closure_notes }}</div>@endif
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-5">
                <div class="d-flex align-items-center gap-3">
                    <select id="historyPageSize" class="form-select form-select-sm w-auto" aria-label="Rows per page"><option>10</option><option>25</option><option>50</option><option>100</option></select>
                    <span class="text-muted fs-7">families per page</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="position-relative"><i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i><input id="historySearch" class="form-control form-control-sm w-250px ps-10" placeholder="Search family masterlist..."></div>
                    <a href="{{ route('disaster.payouts.history.export', ['evacuation_center_id' => $selectedCenter->id]) }}" class="btn btn-sm btn-light-success fw-bold"><i class="ki-duotone ki-file-down fs-3"><span class="path1"></span><span class="path2"></span></i>Export Masterlist</a>
                </div>
            </div>

            <div class="table-responsive history-table-wrap">
                <table class="table align-middle table-row-bordered gy-4 mb-0" id="historyMasterlistTable">
                    <thead><tr><th>Control Number</th><th>Household Head</th><th>Address</th><th>Assigned</th><th>Status</th><th class="min-w-300px">Family Composition</th><th class="text-center">Total</th></tr></thead>
                    <tbody>
                    @forelse($familyRows as $row)
                        <tr class="history-family-row">
                            <td><span class="badge badge-light-primary">{{ $row['control_number'] ?: 'No reference' }}</span></td>
                            <td><div class="fw-bold text-gray-900">{{ $row['household_head'] }}</div><div class="text-muted fs-7">{{ $row['validation_status'] }}</div></td>
                            <td><div class="text-gray-800">{{ $row['address'] ?: '—' }}</div><div class="text-muted fs-7">{{ $row['barangay'] ?: '—' }}</div></td>
                            <td class="text-nowrap">{{ $row['assigned_at']?->format('M d, Y') ?: '—' }}</td>
                            <td><span class="badge badge-light-info">{{ str_replace('_', ' ', $row['workflow_status']) }}</span></td>
                            <td>
                                <div class="composition-list">
                                    <div class="composition-person composition-head"><div><strong>{{ $row['head']['name'] }}</strong><span>Household Head</span></div><small>{{ collect([$row['head']['age'] !== null ? $row['head']['age'].' y/o' : null, $row['head']['sex']])->filter()->implode(' · ') ?: 'Details unavailable' }}</small></div>
                                    @foreach($row['members'] as $member)
                                        <div class="composition-person"><div><strong>{{ $member['name'] }}</strong><span>{{ $member['relationship'] ?: 'Member' }}</span></div><small>{{ collect([$member['age'] !== null ? $member['age'].' y/o' : null, $member['sex']])->filter()->implode(' · ') ?: 'Details unavailable' }}</small></div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center"><span class="history-total-pill">{{ $row['household_size'] }}</span></td>
                        </tr>
                    @empty
                        <tr class="history-empty-row"><td colspan="7" class="text-center py-15"><i class="ki-duotone ki-people fs-3x text-muted"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i><div class="fw-bold mt-3">No assigned families recorded</div><div class="text-muted fs-7">This evacuation center was closed without an assigned household record.</div></td></tr>
                    @endforelse
                    </tbody>
                    @if($familyRows->isNotEmpty())<tfoot><tr><td colspan="5" class="text-end fw-bold">Grand Total</td><td class="fw-bold">{{ number_format($familyRows->count()) }} families</td><td class="text-center fw-bold">{{ number_format($individualCount) }}</td></tr></tfoot>@endif
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-6"><div id="historyInfo" class="text-muted fs-7"></div><div id="historyPagination" class="d-flex align-items-center gap-2"></div></div>
        @else
            <div class="history-empty-state">
                <div class="history-empty-icon"><i class="ki-duotone ki-file-search fs-2x"><span class="path1"></span><span class="path2"></span></i></div>
                @if($centerOptions->isEmpty())
                    <h3>No closed evacuation centers yet</h3><p>Closed centers will appear here together with their retained family records.</p>
                @else
                    <h3>Select a closed evacuation center</h3><p>Choose a center above to view its assigned-family masterlist and family composition.</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')<style>
.history-masterlist-card{border:1px solid var(--bs-gray-200);border-radius:1rem;box-shadow:0 6px 24px rgba(31,53,89,.07)}.history-selector{padding:1.35rem 1.5rem;border:1px solid #dbe8f3;border-radius:1rem;background:linear-gradient(135deg,#f7fbff 0%,#f3f8fc 100%)}.history-center-banner{padding:1.5rem;border-radius:1rem;background:linear-gradient(135deg,#f3f8ff,#f8fbff);border:1px solid #dce9f7}.history-center-stats{display:grid;grid-template-columns:repeat(4,minmax(125px,1fr));gap:.75rem}.history-center-stats>div{min-height:82px;padding:.85rem 1rem;border-radius:.8rem;background:#fff;border:1px solid #e5edf5;display:flex;flex-direction:column;justify-content:center}.history-center-stats span{color:var(--bs-gray-600);font-size:.76rem;margin-bottom:.25rem}.history-center-stats strong{font-size:1.2rem;color:var(--bs-gray-900)}.history-closure-note{padding:.8rem 1rem;border-radius:.7rem;background:#fff;border-left:4px solid var(--bs-primary);color:var(--bs-gray-700)}.history-masterlist-card thead th{background:#1f4e78;color:#fff!important;padding:1rem .85rem;font-size:.76rem;text-transform:uppercase;letter-spacing:.02em;white-space:nowrap}.history-masterlist-card thead th:first-child{border-radius:.65rem 0 0 .65rem}.history-masterlist-card thead th:last-child{border-radius:0 .65rem .65rem 0}.history-masterlist-card tfoot td{background:#d9eaf7;border-top:2px solid #1f4e78}.composition-list{display:grid;gap:.45rem}.composition-person{display:flex;align-items-center;justify-content:space-between;gap:1rem;padding:.45rem .65rem;border-radius:.55rem;background:var(--bs-gray-100)}.composition-person>div{display:flex;flex-direction:column}.composition-person strong{font-size:.8rem;color:var(--bs-gray-900)}.composition-person span,.composition-person small{font-size:.7rem;color:var(--bs-gray-600)}.composition-head{background:#e9f4ff;border:1px solid #d3e9fc}.history-total-pill{display:inline-grid;place-items:center;width:36px;height:36px;border-radius:50%;background:#e9f4ff;color:#1676bd;font-weight:700}.history-table-wrap{min-height:260px}.history-empty-state{min-height:360px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:var(--bs-gray-600)}.history-empty-state h3{color:var(--bs-gray-900);margin:1rem 0 .35rem}.history-empty-state p{max-width:480px}.history-empty-icon{width:64px;height:64px;border-radius:18px;display:grid;place-items:center;background:#eaf5ff;color:var(--bs-primary)}.history-page-btn{width:34px;height:34px;border:0;border-radius:9px;background:transparent;color:var(--bs-gray-600)}.history-page-btn.active{background:var(--bs-primary);color:#fff}.history-page-btn:disabled{opacity:.35}@media(max-width:1199px){.history-center-stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:767px){.history-masterlist-card .card-header{gap:1rem}.history-masterlist-card .card-toolbar{width:100%}.history-masterlist-card .card-toolbar .btn{width:100%}.history-center-stats{grid-template-columns:1fr 1fr}.w-250px{width:210px!important}}@media(max-width:480px){.history-center-stats{grid-template-columns:1fr}}
</style>@endpush

@push('scripts')<script>
document.addEventListener('DOMContentLoaded',function(){const selector=document.getElementById('evacuation_center_id');if(selector)selector.addEventListener('change',()=>document.getElementById('closedCenterSelector').submit());const table=document.getElementById('historyMasterlistTable');if(!table)return;const bodyRows=[...table.querySelectorAll('tbody tr.history-family-row')],search=document.getElementById('historySearch'),size=document.getElementById('historyPageSize'),info=document.getElementById('historyInfo'),pager=document.getElementById('historyPagination');let page=1;function render(){const term=search.value.trim().toLowerCase(),visible=bodyRows.filter(row=>row.innerText.toLowerCase().includes(term)),per=Number(size.value),pages=Math.max(1,Math.ceil(visible.length/per));page=Math.min(page,pages);bodyRows.forEach(row=>row.hidden=true);visible.slice((page-1)*per,page*per).forEach(row=>row.hidden=false);const start=visible.length?(page-1)*per+1:0,end=Math.min(page*per,visible.length);info.textContent=`Showing ${start} to ${end} of ${visible.length} assigned families`;pager.innerHTML='';const button=(label,target,disabled,active=false)=>{const item=document.createElement('button');item.type='button';item.className='history-page-btn'+(active?' active':'');item.textContent=label;item.disabled=disabled;item.onclick=()=>{page=target;render()};pager.appendChild(item)};button('‹',page-1,page===1);for(let current=1;current<=pages;current++)if(current===1||current===pages||Math.abs(current-page)<=1)button(current,current,false,current===page);else if(!pager.lastElementChild||pager.lastElementChild.textContent!=='…')button('…',page,true);button('›',page+1,page===pages)}search.addEventListener('input',()=>{page=1;render()});size.addEventListener('change',()=>{page=1;render()});render()});
</script>@endpush
