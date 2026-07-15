@extends('layouts.dashboard.main') @section('content')
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<form method="post" action="{{route('disaster.payroll.action')}}" class="card card-flush shadow-sm">@csrf<div class="card-header"><div class="card-title"><h3>Connected Payroll Preparation</h3></div><div class="card-toolbar gap-2"><input name="amount" type="number" min="0" step=".01" class="form-control w-150px" placeholder="Amount"><button name="action" value="ready" class="btn btn-light-success">Mark Payroll Ready</button><button name="action" value="submit" class="btn btn-danger">Submit for Payroll</button></div></div><div class="card-body"><div class="table-responsive"><table class="table table-row-dashed"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.pay-check').forEach(x=>x.checked=this.checked)"></th><th>DAFAC</th><th>Household</th><th>Barangay</th><th>Center</th><th>Housing</th><th>Status</th><th></th></tr></thead><tbody>@forelse($families as $family)<tr><td><input class="pay-check" type="checkbox" name="family_ids[]" value="{{$family->id}}"></td><td>{{$family->dafacRecord?->reference_number}}</td><td class="payroll-household-name">{{$family->household_head_full_name}}</td><td>{{$family->barangay?->name}}</td><td>{{$family->evacuationCenter?->name??'Unassigned'}}</td><td>{{$family->housing_condition}}</td><td>@include('disaster.partials.status-badge',['status'=>$family->status->value])</td><td><a href="{{route('disaster.families.show',$family)}}" class="btn btn-sm btn-light">Open</a></td></tr>@empty<tr><td colspan="8" class="text-center py-10">No validated payroll records.</td></tr>@endforelse</tbody></table></div>{{$families->links()}}</div></form>@endsection

@push('styles')
<style>
    .payroll-household-name {
        color: #071437 !important;
        font-weight: 700;
    }
</style>
@endpush
