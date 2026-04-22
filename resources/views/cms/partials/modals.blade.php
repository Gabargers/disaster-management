{{-- CREATE MODAL --}}
<div class="modal fade" id="cmsCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ $cms['routes']['store'] }}" enctype="multipart/form-data">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus me-2"></i> Add {{ ucfirst($cms['resource']) }}
                </h5>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="row g-5">
                    @foreach ($cms['fields'] as $f)
                        @continue(($f['type'] ?? null) === 'repeater' || !isset($f['name']))

                        <div class="col-md-{{ $f['col'] ?? 6 }}">
                            @if (($f['type'] ?? 'text') === 'select')
                                <x-form.select :label="$f['label'] ?? null" :name="$f['name']" :required="(bool) ($f['required'] ?? false)" :placeholder="$f['placeholder'] ?? null" :dropdownParent="'#cmsCreateModal'"
                                    :useSelect2="(bool) ($f['select2'] ?? true)" :options="$f['options'] ?? []" />
                            @else
                                <x-form.input :label="$f['label'] ?? null" :name="$f['name']" :type="$f['type'] ?? 'text'" :required="(bool) ($f['required'] ?? false)" :min="$f['min'] ?? null"
                                    :max="$f['max'] ?? null" :step="$f['step'] ?? null" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="cmsEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" id="cmsEditForm" action="#" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit {{ ucfirst($cms['resource']) }}
                </h5>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id">

                <div class="row g-5">
                    @foreach ($cms['fields'] as $f)
                        @continue(($f['type'] ?? null) === 'repeater' || !isset($f['name']))

                        <div class="col-md-{{ $f['col'] ?? 6 }}">
                            @if (($f['type'] ?? 'text') === 'select')
                                <x-form.select :label="$f['label'] ?? null" :name="$f['name']" :required="(bool) ($f['required'] ?? false)" :placeholder="$f['placeholder'] ?? null"
                                    :dropdownParent="'#cmsEditModal'" :useSelect2="(bool) ($f['select2'] ?? true)" :options="$f['options'] ?? []" />
                            @else
                                <x-form.input :label="$f['label'] ?? null" :name="$f['name']" :type="$f['type'] ?? 'text'" :required="(bool) ($f['required'] ?? false)" :min="$f['min'] ?? null"
                                    :max="$f['max'] ?? null" :step="$f['step'] ?? null" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="cmsDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="cmsDeleteForm" action="#">
            @csrf
            @method('DELETE')

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-trash me-2"></i> Delete</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="text-gray-700">
                    Are you sure you want to delete <span class="fw-bold" id="delete_name">this item</span>?
                </div>

                <ul class="mt-4 mb-0" id="delete_details"></ul>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $stepChunks = collect($cms['fields'])->groupBy('wizard_step');
@endphp

{{-- CREATE MODAL WITH STEPPER --}}
<div class="modal fade" id="cmsCreateStepperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-plus me-2"></i> Add {{ ucfirst($cms['resource']) }}
                </h2>

                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body py-lg-10 px-lg-10">
                <form class="form" id="cmsCreateStepperForm" method="POST" action="{{ $cms['routes']['store'] }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid"
                        id="cms_create_stepper">

                        {{-- STEPPER NAV --}}
                        <div class="d-flex justify-content-center flex-row-auto w-100 w-xl-300px">
                            <div class="stepper-nav ps-lg-10">
                                @foreach ($stepChunks as $stepNumber => $chunk)
                                    <div class="stepper-item {{ $loop->first ? 'current' : '' }}" data-kt-stepper-element="nav">
                                        <div class="stepper-wrapper">
                                            <div class="stepper-icon w-40px h-40px">
                                                <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                <span class="stepper-number">{{ $stepNumber }}</span>
                                            </div>
                                            <div class="stepper-label">
                                                <h3 class="stepper-title">
                                                    {{ $cms['steps'][$stepNumber]['title'] ?? 'Step ' . $stepNumber }}
                                                </h3>
                                                <div class="stepper-desc">
                                                    {{ $cms['steps'][$stepNumber]['description'] ?? 'Complete this step' }}
                                                </div>
                                            </div>
                                        </div>

                                        @if (!$loop->last)
                                            <div class="stepper-line h-40px"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- STEPPER CONTENT --}}
                        <div class="flex-row-fluid py-lg-5 px-lg-15">
                            @foreach ($stepChunks as $stepNumber => $chunk)
                                <div class="{{ $loop->first ? 'current' : '' }}" data-kt-stepper-element="content">
                                    <div class="w-100">
                                        <div class="row g-5">
                                            @foreach ($chunk as $f)
                                                @if (($f['type'] ?? 'text') === 'repeater')
                                                    <div class="col-md-{{ $f['col'] ?? 12 }}">
                                                        <div class="card card-bordered">
                                                            <div class="card-header">
                                                                <h3 class="card-title">{{ $f['label'] ?? 'Repeater' }}</h3>
                                                            </div>

                                                            <div class="card-body">
                                                                <div class="cms-repeater"
                                                                    data-repeater="{{ \Illuminate\Support\Str::slug($f['label'] ?? 'repeater') }}">

                                                                    <div class="cms-repeater-items">
                                                                        <div
                                                                            class="cms-repeater-item border rounded p-5 mb-5 position-relative">
                                                                            <div class="row g-5">
                                                                                @foreach ($f['fields'] as $rf)
                                                                                    <div class="col-md-{{ $rf['col'] ?? 6 }}">
                                                                                        @if (($rf['type'] ?? 'text') === 'select')
                                                                                            <x-form.select :label="$rf['label'] ?? null"
                                                                                                :name="$rf['name']" :required="(bool) ($rf['required'] ??
                                                                                                    false)"
                                                                                                :placeholder="$rf['placeholder'] ?? null" :dropdownParent="'#cmsCreateStepperModal'"
                                                                                                :useSelect2="(bool) ($rf['select2'] ?? true)" :options="$rf['options'] ?? []" />
                                                                                        @else
                                                                                            <x-form.input :label="$rf['label'] ?? null"
                                                                                                :name="$rf['name']" :type="$rf['type'] ?? 'text'"
                                                                                                :required="(bool) ($rf['required'] ??
                                                                                                    false)" :min="$rf['min'] ?? null"
                                                                                                :max="$rf['max'] ?? null" :step="$rf['step'] ?? null" />
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>

                                                                            <div class="mt-4 text-end">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-light-danger cms-remove-repeater-row">
                                                                                    <i class="fas fa-trash me-1"></i> Remove
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="d-flex">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary cms-add-repeater-row">
                                                                            <i class="fas fa-plus me-1"></i> Add Personnel
                                                                        </button>
                                                                    </div>

                                                                    <template class="cms-repeater-template">
                                                                        <div
                                                                            class="cms-repeater-item border rounded p-5 mb-5 position-relative">
                                                                            <div class="row g-5">
                                                                                @foreach ($f['fields'] as $rf)
                                                                                    <div class="col-md-{{ $rf['col'] ?? 6 }}">
                                                                                        @if (($rf['type'] ?? 'text') === 'select')
                                                                                            <x-form.select :label="$rf['label'] ?? null"
                                                                                                :name="$rf['name']" :required="(bool) ($rf['required'] ??
                                                                                                    false)"
                                                                                                :placeholder="$rf['placeholder'] ?? null" :dropdownParent="'#cmsCreateStepperModal'"
                                                                                                :useSelect2="(bool) ($rf['select2'] ?? true)" :options="$rf['options'] ?? []" />
                                                                                        @else
                                                                                            <x-form.input :label="$rf['label'] ?? null"
                                                                                                :name="$rf['name']" :type="$rf['type'] ?? 'text'"
                                                                                                :required="(bool) ($rf['required'] ??
                                                                                                    false)" :min="$rf['min'] ?? null"
                                                                                                :max="$rf['max'] ?? null" :step="$rf['step'] ?? null" />
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>

                                                                            <div class="mt-4 text-end">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-light-danger cms-remove-repeater-row">
                                                                                    <i class="fas fa-trash me-1"></i> Remove
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="col-md-{{ $f['col'] ?? 6 }}">
                                                        @if (($f['type'] ?? 'text') === 'select')
                                                            <x-form.select :label="$f['label'] ?? null" :name="$f['name']" :required="(bool) ($f['required'] ?? false)"
                                                                :placeholder="$f['placeholder'] ?? null" :dropdownParent="'#cmsCreateStepperModal'" :useSelect2="(bool) ($f['select2'] ?? true)"
                                                                :options="$f['options'] ?? []" />
                                                        @else
                                                            <x-form.input :label="$f['label'] ?? null" :name="$f['name']" :type="$f['type'] ?? 'text'"
                                                                :required="(bool) ($f['required'] ?? false)" :min="$f['min'] ?? null" :max="$f['max'] ?? null"
                                                                :step="$f['step'] ?? null" />
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- ACTIONS --}}
                            <div class="d-flex flex-stack pt-10">
                                <div class="me-2">
                                    <button type="button" class="btn btn-lg btn-light-primary" data-kt-stepper-action="previous">
                                        <i class="ki-duotone ki-arrow-left fs-3 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Back
                                    </button>
                                </div>

                                <div>
                                    <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">
                                        Continue
                                        <i class="ki-duotone ki-arrow-right fs-3 ms-1 me-0">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>

                                    <button type="submit" class="btn btn-lg btn-success d-none" data-kt-stepper-action="submit">
                                        <i class="fas fa-save me-1"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- EDIT MODAL WITH STEPPER --}}
<div class="modal fade" id="cmsEditStepperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-edit me-2"></i> Edit {{ ucfirst($cms['resource']) }}
                </h2>

                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body py-lg-10 px-lg-10">
                <form class="form" id="cmsEditStepperForm" method="POST" action="#" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid" id="cms_edit_stepper">

                        {{-- STEPPER NAV --}}
                        <div class="d-flex justify-content-center flex-row-auto w-100 w-xl-300px">
                            <div class="stepper-nav ps-lg-10">
                                @foreach ($stepChunks as $stepNumber => $chunk)
                                    <div class="stepper-item {{ $loop->first ? 'current' : '' }}" data-kt-stepper-element="nav">
                                        <div class="stepper-wrapper">
                                            <div class="stepper-icon w-40px h-40px">
                                                <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                <span class="stepper-number">{{ $stepNumber }}</span>
                                            </div>
                                            <div class="stepper-label">
                                                <h3 class="stepper-title">
                                                    {{ $cms['steps'][$stepNumber]['title'] ?? 'Step ' . $stepNumber }}
                                                </h3>
                                                <div class="stepper-desc">
                                                    {{ $cms['steps'][$stepNumber]['description'] ?? 'Complete this step' }}
                                                </div>
                                            </div>
                                        </div>

                                        @if (!$loop->last)
                                            <div class="stepper-line h-40px"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- STEPPER CONTENT --}}
                        <div class="flex-row-fluid py-lg-5 px-lg-15">
                            @foreach ($stepChunks as $stepNumber => $chunk)
                                <div class="{{ $loop->first ? 'current' : '' }}" data-kt-stepper-element="content">
                                    <div class="w-100">
                                        <div class="row g-5">
                                            @foreach ($chunk as $f)
                                                @if (($f['type'] ?? 'text') === 'repeater')
                                                    <div class="col-md-{{ $f['col'] ?? 12 }}">
                                                        <div class="card card-bordered">
                                                            <div class="card-header">
                                                                <h3 class="card-title">{{ $f['label'] ?? 'Repeater' }}</h3>
                                                            </div>

                                                            <div class="card-body">
                                                                <div class="cms-repeater"
                                                                    data-repeater="{{ \Illuminate\Support\Str::slug($f['label'] ?? 'repeater') }}">

                                                                    <div class="cms-repeater-items">
                                                                        <div
                                                                            class="cms-repeater-item border rounded p-5 mb-5 position-relative">
                                                                            <div class="row g-5">
                                                                                @foreach ($f['fields'] as $rf)
                                                                                    @php
                                                                                        $baseName = str_replace('[]', '', $rf['name']);
                                                                                    @endphp

                                                                                    <div class="col-md-{{ $rf['col'] ?? 6 }}">
                                                                                        @if (($rf['type'] ?? 'text') === 'select')
                                                                                            <x-form.select :label="$rf['label'] ?? null"
                                                                                                :name="$baseName . '[]'" :required="(bool) ($rf['required'] ??
                                                                                                    false)"
                                                                                                :placeholder="$rf['placeholder'] ?? null" :dropdownParent="'#cmsEditStepperModal'"
                                                                                                :useSelect2="(bool) ($rf['select2'] ?? true)" :options="$rf['options'] ?? []" />
                                                                                        @else
                                                                                            <x-form.input :label="$rf['label'] ?? null"
                                                                                                :name="$baseName . '[]'" :type="$rf['type'] ?? 'text'"
                                                                                                :required="(bool) ($rf['required'] ??
                                                                                                    false)" :min="$rf['min'] ?? null"
                                                                                                :max="$rf['max'] ?? null" :step="$rf['step'] ?? null" />
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>

                                                                            <div class="mt-4 text-end">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-light-danger cms-remove-repeater-row">
                                                                                    <i class="fas fa-trash me-1"></i> Remove
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="d-flex">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary cms-add-repeater-row">
                                                                            <i class="fas fa-plus me-1"></i> Add Personnel
                                                                        </button>
                                                                    </div>

                                                                    <template class="cms-repeater-template">
                                                                        <div
                                                                            class="cms-repeater-item border rounded p-5 mb-5 position-relative">
                                                                            <div class="row g-5">
                                                                                @foreach ($f['fields'] as $rf)
                                                                                    @php
                                                                                        $baseName = str_replace('[]', '', $rf['name']);
                                                                                        $editName = 'edit_' . $baseName . '[]';
                                                                                    @endphp

                                                                                    <div class="col-md-{{ $rf['col'] ?? 6 }}">
                                                                                        @if (($rf['type'] ?? 'text') === 'select')
                                                                                            <x-form.select :label="$rf['label'] ?? null"
                                                                                                :name="$baseName . '[]'" :required="(bool) ($rf['required'] ??
                                                                                                    false)"
                                                                                                :placeholder="$rf['placeholder'] ?? null" :dropdownParent="'#cmsEditStepperModal'"
                                                                                                :useSelect2="(bool) ($rf['select2'] ?? true)" :options="$rf['options'] ?? []" />
                                                                                        @else
                                                                                            <x-form.input :label="$rf['label'] ?? null"
                                                                                                :name="$baseName . '[]'" :type="$rf['type'] ?? 'text'"
                                                                                                :required="(bool) ($rf['required'] ??
                                                                                                    false)" :min="$rf['min'] ?? null"
                                                                                                :max="$rf['max'] ?? null" :step="$rf['step'] ?? null" />
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>

                                                                            <div class="mt-4 text-end">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-light-danger cms-remove-repeater-row">
                                                                                    <i class="fas fa-trash me-1"></i> Remove
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="col-md-{{ $f['col'] ?? 6 }}">
                                                        @if (($f['type'] ?? 'text') === 'select')
                                                            <x-form.select :label="$f['label'] ?? null" :name="$f['name']" :id="'edit_' . $f['name']"
                                                                :required="(bool) ($f['required'] ?? false)" :placeholder="$f['placeholder'] ?? null" :dropdownParent="'#cmsEditStepperModal'"
                                                                :useSelect2="(bool) ($f['select2'] ?? true)" :options="$f['options'] ?? []" />
                                                        @else
                                                            <x-form.input :label="$f['label'] ?? null" :name="$f['name']" :id="'edit_' . $f['name']"
                                                                :type="$f['type'] ?? 'text'" :required="(bool) ($f['required'] ?? false)" :min="$f['min'] ?? null"
                                                                :max="$f['max'] ?? null" :step="$f['step'] ?? null" />
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- ACTIONS --}}
                            <div class="d-flex flex-stack pt-10">
                                <div class="me-2">
                                    <button type="button" class="btn btn-lg btn-light-primary" data-kt-stepper-action="previous">
                                        <i class="ki-duotone ki-arrow-left fs-3 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Back
                                    </button>
                                </div>

                                <div>
                                    <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">
                                        Continue
                                        <i class="ki-duotone ki-arrow-right fs-3 ms-1 me-0">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>

                                    <button type="submit" class="btn btn-lg btn-success d-none" data-kt-stepper-action="submit">
                                        <i class="fas fa-save me-1"></i> Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="violationPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Violation Photo</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>

            <div class="modal-body text-center">
                <div id="violationPhotoLoading" class="py-10 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-3 text-muted">Loading photo...</div>
                </div>

                <div id="violationPhotoEmpty" class="py-10 d-none">
                    <div class="text-muted">No photo available for this violation record.</div>
                </div>

                <img id="violationPhotoPreview" src="" alt="Violation Photo" class="img-fluid rounded d-none"
                    style="max-height: 500px; object-fit: contain;">
            </div>
        </div>
    </div>
</div>
