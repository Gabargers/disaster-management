@extends('layouts.dashboard.main')

@section('content')
    <div class="row g-5 g-xl-8">
        <div class="col-xl-8">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Housing and Ownership Validation</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-6">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Validated Housing Condition</label>
                            <select class="form-select form-select-solid">
                                <option>Pending Validation</option>
                                <option>Totally Damaged</option>
                                <option>Partially Damaged</option>
                                <option>Water Damage</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Validated House Ownership</label>
                            <select class="form-select form-select-solid">
                                <option>Owner</option>
                                <option>Renter</option>
                                <option>Sharer</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Validation Notes</label>
                            <textarea class="form-control form-control-solid" rows="5" placeholder="Inspection notes, barangay confirmation, or correction instructions"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select form-select-solid">
                                <option>Pending Validation</option>
                                <option>Validated</option>
                                <option>Rejected</option>
                                <option>Needs Correction</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-danger">Save Validation</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold mb-0">Supporting Documents</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="dropzone bg-light rounded border border-dashed p-10 text-center">
                        <i class="ki-duotone ki-file-up fs-3x text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <div class="fw-bold text-gray-800 mt-4">Upload photos or documents</div>
                        <div class="text-muted fs-7">Damage photos, ownership proof, and barangay notes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
