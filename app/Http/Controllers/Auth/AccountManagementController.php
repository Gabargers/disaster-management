<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreManagedAccountRequest;
use App\Http\Requests\Auth\UpdateManagedAccountRequest;
use App\Models\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AccountManagementController extends Controller
{
    private const ROLE_LABELS = [
        'cswdo-coordinator' => 'CSWDO Coordinator',
        'disaster-operation-officer' => 'Validator / Disaster Operation Officer',
        'cares-social-worker' => 'CARES Social Worker',
        'payout-payroll-staff' => 'Payout / Payroll Staff',
    ];

    public function index(): View
    {
        return view('accounts.index', [
            'page_title' => 'Account Management',
            'page_description' => 'Create and manage operational user accounts.',
            'roles' => self::ROLE_LABELS,
        ]);
    }

    public function data(): JsonResponse
    {
        $users = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', StoreManagedAccountRequest::MANAGED_ROLES))
            ->with('roles:id,name')
            ->select(['users.id', 'users.first_name', 'users.middle_name', 'users.last_name', 'users.email', 'users.contact_number', 'users.is_active', 'users.created_at']);

        return DataTables::eloquent($users)
            ->addColumn('full_name', fn (User $user) => collect([$user->first_name, $user->middle_name, $user->last_name])->filter()->join(' '))
            ->addColumn('roles', fn (User $user) => $user->roles
                ->pluck('name')
                ->map(fn (string $role) => self::ROLE_LABELS[$role] ?? str($role)->headline())
                ->join(', '))
            ->editColumn('contact_number', fn (User $user) => $user->contact_number ?: '—')
            ->addColumn('status', fn (User $user) => $user->is_active
                ? '<span class="badge badge-light-success">Active</span>'
                : '<span class="badge badge-light-danger">Inactive</span>')
            ->editColumn('created_at', fn (User $user) => $user->created_at?->format('M d, Y'))
            ->addColumn('action', fn (User $user) => $this->actionButtons($user))
            ->filterColumn('full_name', function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('middle_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function store(StoreManagedAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => collect([$data['first_name'], $data['middle_name'] ?? null, $data['last_name']])->filter()->join(' '),
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'contact_number' => $data['contact_number'],
                'password' => $data['password'],
                'is_active' => $data['is_active'],
            ]);

            $user->syncRoles($data['roles']);
        });

        return back()->with('success', 'Account has been created successfully.');
    }

    public function update(UpdateManagedAccountRequest $request, User $account): RedirectResponse
    {
        $this->ensureManagedAccount($account);
        $data = $request->validated();

        DB::transaction(function () use ($account, $data) {
            $attributes = [
                'name' => collect([$data['first_name'], $data['middle_name'] ?? null, $data['last_name']])->filter()->join(' '),
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'contact_number' => $data['contact_number'],
                'is_active' => $data['is_active'],
            ];

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            $account->update($attributes);
            $account->syncRoles($data['roles']);
        });

        return back()->with('success', 'Account has been updated successfully.');
    }

    public function destroy(User $account): RedirectResponse
    {
        $this->ensureManagedAccount($account);
        $account->delete();

        return back()->with('success', 'Account has been deleted successfully.');
    }

    private function ensureManagedAccount(User $account): void
    {
        abort_if($account->hasAnyRole(['admin', 'superadmin']), 403, 'Administrator accounts cannot be modified here.');
        abort_unless($account->hasAnyRole(StoreManagedAccountRequest::MANAGED_ROLES), 404);
    }

    private function actionButtons(User $user): string
    {
        $payload = e(json_encode([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'is_active' => $user->is_active ? '1' : '0',
            'roles' => $user->roles->pluck('name')->intersect(StoreManagedAccountRequest::MANAGED_ROLES)->values(),
            'update_url' => route('accounts.update', $user),
            'delete_url' => route('accounts.destroy', $user),
        ], JSON_THROW_ON_ERROR));

        return '<div class="d-flex justify-content-center gap-2">'
            .'<button type="button" class="btn btn-sm btn-light-primary js-edit-account" data-account="'.$payload.'"><i class="ki-duotone ki-pencil fs-4"></i> Edit</button>'
            .'<button type="button" class="btn btn-sm btn-light-danger js-delete-account" data-account="'.$payload.'"><i class="ki-duotone ki-trash fs-4"></i> Delete</button>'
            .'</div>';
    }
}
