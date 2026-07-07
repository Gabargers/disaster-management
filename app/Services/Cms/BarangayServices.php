<?php

namespace App\Services\Cms;

use App\Models\Cms\Barangay;
use App\Services\Crud\CrudServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class BarangayServices
{
    public function __construct(
        private readonly CrudServices $crudServices
    ) {}

    public function cmsConfig(string $role): array
    {
        return [
            'resource' => 'barangay',
            'page_title' => 'Barangay Management',
            'routes' => [
                'data' => route("{$role}.barangay.data"),
                'store' => route("{$role}.barangay.store"),
            ],
            'fields' => [
                [
                    'name' => 'name',
                    'label' => 'Barangay Name',
                    'required' => true,
                    'placeholder' => 'Barangay name',
                    'col' => 6,
                ],
                [
                    'name' => 'code',
                    'label' => 'Barangay Code',
                    'placeholder' => 'Optional code',
                    'col' => 6,
                ],
                [
                    'name' => 'district',
                    'label' => 'District',
                    'placeholder' => 'District / area',
                    'col' => 6,
                ],
                [
                    'name' => 'captain_name',
                    'label' => 'Barangay Captain',
                    'placeholder' => 'Captain name',
                    'col' => 6,
                ],
                [
                    'name' => 'contact_number',
                    'label' => 'Contact Number',
                    'placeholder' => 'Primary contact number',
                    'col' => 6,
                ],
                [
                    'type' => 'select',
                    'name' => 'is_active',
                    'label' => 'Status',
                    'required' => true,
                    'placeholder' => 'Select status',
                    'options' => [
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ],
                    'col' => 6,
                ],
            ],
            'datatableColumns' => [
                ['data' => 'name', 'name' => 'name', 'title' => 'Barangay'],
                ['data' => 'code', 'name' => 'code', 'title' => 'Code'],
                ['data' => 'district', 'name' => 'district', 'title' => 'District'],
                ['data' => 'captain_name', 'name' => 'captain_name', 'title' => 'Captain'],
                ['data' => 'contact_number', 'name' => 'contact_number', 'title' => 'Contact'],
                ['data' => 'status', 'name' => 'is_active', 'title' => 'Status', 'orderable' => true, 'searchable' => false],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ],
        ];
    }

    public function dataTable(string $role): JsonResponse
    {
        $query = Barangay::query()
            ->select([
                'id',
                'uuid',
                'name',
                'code',
                'district',
                'captain_name',
                'contact_number',
                'is_active',
                'created_at',
            ]);

        return DataTables::eloquent($query)
            ->editColumn('code', fn (Barangay $barangay) => $barangay->code ?: '-')
            ->editColumn('district', fn (Barangay $barangay) => $barangay->district ?: '-')
            ->editColumn('captain_name', fn (Barangay $barangay) => $barangay->captain_name ?: '-')
            ->editColumn('contact_number', fn (Barangay $barangay) => $barangay->contact_number ?: '-')
            ->addColumn('status', fn (Barangay $barangay) => $this->statusBadge($barangay))
            ->addColumn('action', fn (Barangay $barangay) => $this->actionButtons($barangay, $role))
            ->filterColumn('status', function (Builder $query, string $keyword) {
                $keyword = strtolower($keyword);

                if (str_contains('active', $keyword)) {
                    $query->where('is_active', true);
                }

                if (str_contains('inactive', $keyword)) {
                    $query->orWhere('is_active', false);
                }
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function store(array $payload): Barangay
    {
        return $this->crudServices->store(Barangay::class, $payload, $this->crudOptions());
    }

    public function update(Barangay $barangay, array $payload): Barangay
    {
        return $this->crudServices->update($barangay, $payload, $this->crudOptions());
    }

    public function delete(Barangay $barangay): bool
    {
        return $this->crudServices->delete($barangay, [
            'log_name' => 'barangay',
            'event' => 'barangay_deleted',
            'event_error' => 'barangay_delete_failed',
        ]);
    }

    private function crudOptions(): array
    {
        return [
            'only' => [
                'name',
                'code',
                'district',
                'captain_name',
                'contact_number',
                'is_active',
            ],
            'log_name' => 'barangay',
            'log_payload_keys' => [
                'name',
                'code',
                'district',
                'captain_name',
                'contact_number',
                'is_active',
            ],
        ];
    }

    private function statusBadge(Barangay $barangay): string
    {
        if ($barangay->is_active) {
            return '<span class="badge badge-light-success">Active</span>';
        }

        return '<span class="badge badge-light-danger">Inactive</span>';
    }

    private function actionButtons(Barangay $barangay, string $role): string
    {
        $updateUrl = route("{$role}.barangay.update", $barangay);
        $deleteUrl = route("{$role}.barangay.destroy", $barangay);

        $data = e(json_encode([
            'name' => $barangay->name,
            'code' => $barangay->code,
            'district' => $barangay->district,
            'captain_name' => $barangay->captain_name,
            'contact_number' => $barangay->contact_number,
            'is_active' => $barangay->is_active ? '1' : '0',
            'updateUrl' => $updateUrl,
            'deleteUrl' => $deleteUrl,
        ]));

        return <<<HTML
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-light-primary btn-cms-edit"
                    data-name="{$this->escape($barangay->name)}"
                    data-code="{$this->escape($barangay->code)}"
                    data-district="{$this->escape($barangay->district)}"
                    data-captain-name="{$this->escape($barangay->captain_name)}"
                    data-contact-number="{$this->escape($barangay->contact_number)}"
                    data-is-active="{$this->escape($barangay->is_active ? '1' : '0')}"
                    data-update-url="{$this->escape($updateUrl)}">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light-danger btn-cms-delete"
                    data-name="{$this->escape($barangay->name)}"
                    data-code="{$this->escape($barangay->code)}"
                    data-district="{$this->escape($barangay->district)}"
                    data-captain-name="{$this->escape($barangay->captain_name)}"
                    data-contact-number="{$this->escape($barangay->contact_number)}"
                    data-is-active="{$this->escape($barangay->is_active ? '1' : '0')}"
                    data-delete-url="{$this->escape($deleteUrl)}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        HTML;
    }

    private function escape(?string $value): string
    {
        return e($value ?? '');
    }
}
