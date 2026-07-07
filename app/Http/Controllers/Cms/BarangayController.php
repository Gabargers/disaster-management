<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\BarangayRequest;
use App\Models\Cms\Barangay;
use App\Services\Cms\BarangayServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BarangayController extends Controller
{
    public function __construct(
        private readonly BarangayServices $barangayServices
    ) {}

    public function index(): View
    {
        return view('cms.index', [
            'cms' => $this->barangayServices->cmsConfig($this->role()),
            'page_title' => 'Barangay Management',
            'page_description' => 'Manage barangay master records for disaster response operations.',
        ]);
    }

    public function data(): JsonResponse
    {
        return $this->barangayServices->dataTable($this->role());
    }

    public function store(BarangayRequest $request): RedirectResponse
    {
        $this->barangayServices->store($request->validated());

        return back()->with('success', 'Barangay has been created successfully.');
    }

    public function update(BarangayRequest $request, Barangay $barangay): RedirectResponse
    {
        $this->barangayServices->update($barangay, $request->validated());

        return back()->with('success', 'Barangay has been updated successfully.');
    }

    public function destroy(Barangay $barangay): RedirectResponse
    {
        $this->barangayServices->delete($barangay);

        return back()->with('success', 'Barangay has been deleted successfully.');
    }

    private function role(): string
    {
        return Auth::user()?->getRoleNames()->first() ?: 'superadmin';
    }
}
