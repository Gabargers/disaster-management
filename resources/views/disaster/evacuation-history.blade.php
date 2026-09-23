@extends('layouts.dashboard.main')
@section('content')
@php($numericColumns = ['capacity', 'families_recorded', 'individuals_recorded'])
<div class="card card-flush history-report-card">
    <div class="card-header border-0 pt-6 align-items-center">
        <div class="card-title d-block"><h2 class="fw-bold text-gray-900 mb-1">Evacuation Center History</h2><div class="text-muted fs-7">Search, review, and export retained records from closed evacuation centers.</div></div>
        <div class="card-toolbar gap-3">
            <a href="{{ route('disaster.payouts.index') }}" class="btn btn-light-primary fw-bold">Active Centers</a>
            <a href="{{ route('disaster.payouts.history.export', request()->query()) }}" class="btn btn-light-success fw-bold"><i class="ki-duotone ki-file-down fs-3"><span class="path1"></span><span class="path2"></span></i>Export Excel</a>
            <button class="btn btn-primary fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#historyConfigurator"><i class="ki-duotone ki-setting-4 fs-3"><span class="path1"></span><span class="path2"></span></i>Configure History</button>
        </div>
    </div>
    <div class="card-body pt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
            <select id="historyPageSize" class="form-select form-select-sm w-auto" aria-label="Rows per page"><option>10</option><option>25</option><option>50</option><option>100</option></select>
            <div class="position-relative"><i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i><input id="historySearch" class="form-control form-control-sm w-250px ps-10" placeholder="Search closed records..."></div>
        </div>
        <div class="table-responsive history-table-wrap"><table class="table align-middle table-row-bordered gy-4 mb-0" id="historyReportTable">
            <thead><tr>@foreach($selected as $key)<th class="text-uppercase text-nowrap">{{ $columns[$key] }}</th>@endforeach<th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>@foreach($selected as $key)<td class="{{ in_array($key, $numericColumns) ? 'text-center' : ($key === 'center' ? 'fw-semibold text-gray-900' : '') }}">{{ $row[$key] }}</td>@endforeach<td class="text-end text-nowrap"><a class="btn btn-sm btn-light-primary" href="{{ route('disaster.payouts.centers.show', $row['id']) }}">View Records</a></td></tr>
            @empty
                <tr class="history-empty-row"><td colspan="{{ count($selected) + 1 }}" class="text-center py-15"><i class="ki-duotone ki-time fs-3x text-muted"><span class="path1"></span><span class="path2"></span></i><div class="fw-bold mt-3">No closed evacuation centers found</div><div class="text-muted fs-7">Try changing the history filters.</div></td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty())<tfoot><tr class="fw-bold">@foreach($selected as $key)<td class="{{ in_array($key, $numericColumns) ? 'text-center' : '' }}">{{ $loop->first ? 'Grand Total' : (in_array($key, $numericColumns) ? number_format($rows->sum($key)) : '') }}</td>@endforeach<td></td></tr></tfoot>@endif
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-6"><div id="historyInfo" class="text-muted fs-7"></div><div id="historyPagination" class="d-flex align-items-center gap-2"></div></div>
    </div>
</div>

<div class="offcanvas offcanvas-end history-configurator" tabindex="-1" id="historyConfigurator" aria-labelledby="historyConfiguratorLabel"><form method="get" action="{{ route('disaster.payouts.history') }}" class="h-100 d-flex flex-column" id="historyConfigForm">
    <div class="offcanvas-header border-bottom px-7 py-5"><div><h3 id="historyConfiguratorLabel" class="fw-bold mb-1">Configure Evacuation History</h3><div class="text-muted fs-7">Filter closed centers and choose the report headers.</div></div><button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="offcanvas"><i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i></button></div>
    <div class="offcanvas-body px-7 py-6"><div class="row g-5">
        <div class="col-12"><label class="form-label fw-semibold">Disaster Event</label><select name="disaster_id" class="form-select"><option value="">All disaster events</option>@foreach($disasters as $disaster)<option value="{{ $disaster->id }}" @selected((string)request('disaster_id') === (string)$disaster->id)>{{ $disaster->name }} — {{ $disaster->incident_date?->format('M d, Y') }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Disaster Type</label><select name="disaster_type" class="form-select"><option value="">All types</option>@foreach(['Earthquake','Fire','Typhoon','Flood'] as $type)<option value="{{ $type }}" @selected(request('disaster_type') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">District</label><select name="district" class="form-select"><option value="">All districts</option>@foreach($barangays->pluck('district')->filter()->unique()->sort() as $district)<option value="{{ $district }}" @selected(request('district') === $district)>{{ $district }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Barangay</label><select name="barangay_id" class="form-select"><option value="">All barangays</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected((string)request('barangay_id') === (string)$barangay->id)>{{ $barangay->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Evacuation Center</label><select name="evacuation_center_id" class="form-select"><option value="">All closed centers</option>@foreach($centerOptions as $center)<option value="{{ $center->id }}" @selected((string)request('evacuation_center_id') === (string)$center->id)>{{ $center->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Closed From</label><input type="date" name="closed_from" value="{{ request('closed_from') }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Closed To</label><input type="date" name="closed_to" value="{{ request('closed_to') }}" class="form-control"></div>
    </div>
    <div class="separator my-7"></div><div class="d-flex justify-content-between align-items-center mb-5"><div class="fw-bold fs-5"><i class="ki-duotone ki-row-horizontal text-primary fs-3 me-2"><span class="path1"></span><span class="path2"></span></i>Headers</div><div class="d-flex gap-4"><button type="button" class="btn btn-link btn-sm p-0" id="selectAllHistoryHeaders">Select All</button><button type="button" class="btn btn-link btn-sm text-muted p-0" id="defaultHistoryHeaders">Use Defaults</button></div></div>
    @php($headerGroups = [
        'Center Details' => ['center','disaster_title','disaster_type','district','barangay','address'],
        'Operations' => ['capacity','families_recorded','individuals_recorded','date_opened'],
        'Closure' => ['closed_at','closed_by','closure_notes'],
    ])
    <div id="historyHeaders">@foreach($headerGroups as $groupLabel => $groupColumns)<div class="history-header-group mb-6"><div class="fw-bold text-gray-900 mb-4">{{ $groupLabel }}</div><div class="row g-4 ps-8">@foreach($groupColumns as $key)<div class="col-6"><label class="form-check form-check-custom form-check-solid"><input class="form-check-input history-column" type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $checkedColumns))><span class="form-check-label text-gray-700">{{ $columns[$key] }}</span></label></div>@endforeach</div></div>@endforeach</div>
    </div>
    <div class="border-top px-7 py-5 d-flex justify-content-end gap-3 bg-body"><a href="{{ route('disaster.payouts.history') }}" class="btn btn-light">Reset</a><button class="btn btn-primary" type="submit"><i class="ki-duotone ki-check fs-3"><span class="path1"></span><span class="path2"></span></i>Apply History</button></div>
</form></div>
@endsection

@push('styles')<style>
.history-report-card{border:1px solid var(--bs-gray-200);border-radius:1rem;box-shadow:0 6px 24px rgba(31,53,89,.07)}.history-report-card thead th{background:#1f4e78;color:#fff!important;text-align:center;padding:1rem .85rem;font-size:.78rem;letter-spacing:.02em;border-bottom:0}.history-report-card thead th:first-child{border-radius:.65rem 0 0 .65rem}.history-report-card thead th:last-child{border-radius:0 .65rem .65rem 0}.history-report-card tfoot td{background:#d9eaf7;border-top:2px solid #1f4e78}.history-table-wrap{min-height:300px}.history-configurator{width:min(650px,100vw)!important}.history-configurator .offcanvas-body{overflow-y:auto}.history-page-btn{width:34px;height:34px;border:0;border-radius:9px;background:transparent;color:var(--bs-gray-600)}.history-page-btn.active{background:var(--bs-primary);color:#fff}.history-page-btn:disabled{opacity:.35}@media(max-width:767px){.history-report-card .card-header{gap:1rem}.history-report-card .card-toolbar{width:100%}.history-report-card .card-toolbar .btn{flex:1}.w-250px{width:210px!important}}
</style>@endpush

@push('scripts')<script>
document.addEventListener('DOMContentLoaded',function(){const bodyRows=[...document.querySelectorAll('#historyReportTable tbody tr:not(.history-empty-row)')],search=document.getElementById('historySearch'),size=document.getElementById('historyPageSize'),info=document.getElementById('historyInfo'),pager=document.getElementById('historyPagination');let page=1;function render(){const term=search.value.trim().toLowerCase(),visible=bodyRows.filter(row=>row.innerText.toLowerCase().includes(term)),per=Number(size.value),pages=Math.max(1,Math.ceil(visible.length/per));page=Math.min(page,pages);bodyRows.forEach(row=>row.hidden=true);visible.slice((page-1)*per,page*per).forEach(row=>row.hidden=false);const start=visible.length?(page-1)*per+1:0,end=Math.min(page*per,visible.length);info.textContent=`Showing ${start} to ${end} of ${visible.length} closed evacuation centers`;pager.innerHTML='';const button=(label,target,disabled,active=false)=>{const item=document.createElement('button');item.type='button';item.className='history-page-btn'+(active?' active':'');item.textContent=label;item.disabled=disabled;item.onclick=()=>{page=target;render()};pager.appendChild(item)};button('‹',page-1,page===1);for(let current=1;current<=pages;current++)if(current===1||current===pages||Math.abs(current-page)<=1)button(current,current,false,current===page);else if(!pager.lastElementChild||pager.lastElementChild.textContent!=='…')button('…',page,true);button('›',page+1,page===pages)}search.addEventListener('input',()=>{page=1;render()});size.addEventListener('change',()=>{page=1;render()});render();const checks=[...document.querySelectorAll('.history-column')];document.getElementById('selectAllHistoryHeaders').onclick=()=>checks.forEach(check=>check.checked=true);document.getElementById('defaultHistoryHeaders').onclick=()=>checks.forEach(check=>check.checked=true);document.getElementById('historyConfigForm').addEventListener('submit',event=>{if(!checks.some(check=>check.checked)){event.preventDefault();alert('Select at least one history header.')}})});
</script>@endpush
