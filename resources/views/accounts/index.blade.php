@extends('layouts.dashboard.main')

@section('content')
    @include('components.alert')

    <div class="card shadow-sm">
        <div class="card-header align-items-center">
            <div>
                <h3 class="card-title fw-bold mb-1">Account Management</h3>
                <div class="text-muted fs-7">Accounts for validators, coordinators, social workers, and payout staff.</div>
            </div>
            <div class="card-toolbar">
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                    <i class="ki-duotone ki-plus fs-2"></i> Create Account
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="accounts-table" class="table align-middle table-row-dashed fs-6 gy-5" style="width:100%">
                    <thead><tr class="text-gray-700 fw-bold fs-7 text-uppercase">
                        <th>Name</th><th>Email</th><th>Contact</th><th>Role</th><th>Status</th><th>Date Created</th><th>Actions</th>
                    </tr></thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.store') }}">
                @csrf
                <div class="modal-header">
                    <div><h2 class="mb-1">Create Account</h2><div class="text-muted fs-7">Set the user's profile, access role, and temporary password.</div></div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2"></i></button>
                </div>
                <div class="modal-body py-7">
                    @if ($errors->any())
                        <div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="row g-5">
                        <div class="col-md-4"><label class="form-label required">First Name</label><input class="form-control form-control-solid" name="first_name" value="{{ old('first_name') }}" required maxlength="100"></div>
                        <div class="col-md-4"><label class="form-label">Middle Name</label><input class="form-control form-control-solid" name="middle_name" value="{{ old('middle_name') }}" maxlength="100"></div>
                        <div class="col-md-4"><label class="form-label required">Last Name</label><input class="form-control form-control-solid" name="last_name" value="{{ old('last_name') }}" required maxlength="100"></div>
                        <div class="col-md-6"><label class="form-label required">Email Address</label><input type="email" class="form-control form-control-solid" name="email" value="{{ old('email') }}" required></div>
                        <div class="col-md-6"><label class="form-label required">Contact Number</label><input class="form-control form-control-solid" name="contact_number" value="{{ old('contact_number') }}" placeholder="09171234567" required></div>
                        <div class="col-md-8">
                            <label class="form-label required">Roles</label>
                            <select class="form-select form-select-solid" name="roles[]" id="accountRoles" multiple required
                                data-control="select2" data-placeholder="Select one or more roles"
                                data-dropdown-parent="#createAccountModal">
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}" @selected(in_array($value, old('roles', []), true))>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">You may assign more than one role to this account.</div>
                        </div>
                        <div class="col-md-4"><label class="form-label required">Status</label><select class="form-select form-select-solid" name="is_active" required><option value="1" @selected(old('is_active', '1') === '1')>Active</option><option value="0" @selected(old('is_active') === '0')>Inactive</option></select></div>
                        <div class="col-md-6"><label class="form-label required">Temporary Password</label><input type="password" class="form-control form-control-solid" name="password" minlength="8" required autocomplete="new-password"></div>
                        <div class="col-md-6"><label class="form-label required">Confirm Password</label><input type="password" class="form-control form-control-solid" name="password_confirmation" minlength="8" required autocomplete="new-password"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit"><i class="ki-duotone ki-check fs-2"></i> Create Account</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="editAccountForm">
                @csrf @method('PUT')
                <input type="hidden" name="account_id" id="editAccountId" value="{{ old('account_id') }}">
                <div class="modal-header">
                    <div><h2 class="mb-1">Edit Account</h2><div class="text-muted fs-7">Update the user's profile, access roles, status, or password.</div></div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="ki-duotone ki-cross fs-2"></i></button>
                </div>
                <div class="modal-body py-7">
                    @if ($errors->updateAccount->any())
                        <div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul class="mb-0 mt-2">@foreach ($errors->updateAccount->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="row g-5">
                        <div class="col-md-4"><label class="form-label required">First Name</label><input class="form-control form-control-solid" id="editFirstName" name="first_name" required maxlength="100"></div>
                        <div class="col-md-4"><label class="form-label">Middle Name</label><input class="form-control form-control-solid" id="editMiddleName" name="middle_name" maxlength="100"></div>
                        <div class="col-md-4"><label class="form-label required">Last Name</label><input class="form-control form-control-solid" id="editLastName" name="last_name" required maxlength="100"></div>
                        <div class="col-md-6"><label class="form-label required">Email Address</label><input type="email" class="form-control form-control-solid" id="editEmail" name="email" required></div>
                        <div class="col-md-6"><label class="form-label required">Contact Number</label><input class="form-control form-control-solid" id="editContactNumber" name="contact_number" placeholder="09171234567" required></div>
                        <div class="col-md-8">
                            <label class="form-label required">Roles</label>
                            <select class="form-select form-select-solid" id="editRoles" name="roles[]" multiple required data-control="select2" data-placeholder="Select one or more roles" data-dropdown-parent="#editAccountModal">
                                @foreach ($roles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label required">Status</label><select class="form-select form-select-solid" id="editStatus" name="is_active" required><option value="1">Active</option><option value="0">Inactive</option></select></div>
                        <div class="col-12"><div class="notice bg-light-primary border border-primary border-dashed rounded p-4 fs-7">Leave the password fields blank to keep the current password.</div></div>
                        <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control form-control-solid" name="password" minlength="8" autocomplete="new-password"></div>
                        <div class="col-md-6"><label class="form-label">Confirm New Password</label><input type="password" class="form-control form-control-solid" name="password_confirmation" minlength="8" autocomplete="new-password"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit"><i class="ki-duotone ki-check fs-2"></i> Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteAccountForm" class="d-none">@csrf @method('DELETE')</form>
@endpush

@push('scripts')
@php
    $oldEditAccount = [
        'id' => old('account_id'),
        'first_name' => old('first_name'),
        'middle_name' => old('middle_name'),
        'last_name' => old('last_name'),
        'email' => old('email'),
        'contact_number' => old('contact_number'),
        'is_active' => old('is_active'),
        'roles' => old('roles', []),
    ];
@endphp
<script>
$(function () {
    $('#accounts-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: @json(route('accounts.data')),
        order: [[5, 'desc']], pageLength: 10, searchDelay: 350,
        columns: [
            {data: 'full_name', name: 'full_name'}, {data: 'email', name: 'email'},
            {data: 'contact_number', name: 'contact_number'}, {data: 'roles', name: 'roles', orderable: false},
            {data: 'status', name: 'is_active', searchable: false}, {data: 'created_at', name: 'created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        language: {search: '', searchPlaceholder: 'Search accounts...', emptyTable: 'No operational accounts found.'}
    });
    @if ($errors->any()) new bootstrap.Modal(document.getElementById('createAccountModal')).show(); @endif

    const editModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    const fillEditForm = account => {
        const form = document.getElementById('editAccountForm');
        form.action = account.update_url || `{{ url('/accounts') }}/${account.id}`;
        $('#editAccountId').val(account.id);
        $('#editFirstName').val(account.first_name);
        $('#editMiddleName').val(account.middle_name || '');
        $('#editLastName').val(account.last_name);
        $('#editEmail').val(account.email);
        $('#editContactNumber').val(account.contact_number);
        $('#editStatus').val(String(account.is_active));
        $('#editRoles').val(account.roles || []).trigger('change');
        form.querySelectorAll('input[type=password]').forEach(input => input.value = '');
    };

    document.addEventListener('click', async event => {
        const editButton = event.target.closest('.js-edit-account');
        if (editButton) {
            fillEditForm(JSON.parse(editButton.dataset.account));
            editModal.show();
            return;
        }

        const deleteButton = event.target.closest('.js-delete-account');
        if (!deleteButton) return;
        const account = JSON.parse(deleteButton.dataset.account);
        const result = await Swal.fire({
            title: 'Delete account?',
            html: `The account of <strong>${$('<div>').text([account.first_name, account.last_name].join(' ')).html()}</strong> will be permanently deleted.`,
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete account', confirmButtonColor: '#dc3545'
        });
        if (result.isConfirmed) {
            const form = document.getElementById('deleteAccountForm');
            form.action = account.delete_url;
            form.submit();
        }
    });

    @if ($errors->updateAccount->any())
        fillEditForm(@json($oldEditAccount));
        editModal.show();
    @endif
});
</script>
@endpush
