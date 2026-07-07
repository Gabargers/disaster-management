@extends('layouts.dashboard.main')

@section('content')
    <div class="card card-flush shadow-sm mb-8">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Disaster Assistance Family Access Card</h3>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-danger btn-sm">Save Intake</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-6">
                @foreach (['Barangay', 'Date', 'Evacuation Center', 'Type of Disaster'] as $field)
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ $field }}</label>
                        <input type="{{ $field === 'Date' ? 'date' : 'text' }}" class="form-control form-control-solid">
                    </div>
                @endforeach
            </div>

            <div class="separator my-8"></div>

            <h4 class="fw-bold mb-5">Head of Family</h4>
            <div class="row g-6">
                @foreach (['Surname', 'Given Name', 'Middle Name', 'Complete Address', 'Birthdate', 'Age', 'Occupation', 'Monthly Income'] as $field)
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ $field }}</label>
                        <input type="{{ $field === 'Birthdate' ? 'date' : 'text' }}" class="form-control form-control-solid">
                    </div>
                @endforeach
                <div class="col-md-4">
                    <label class="form-label fw-bold">House Ownership</label>
                    <select class="form-select form-select-solid">
                        <option>Owner</option>
                        <option>Renter</option>
                        <option>Sharer</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Housing Condition</label>
                    <select class="form-select form-select-solid">
                        <option>Totally Damaged</option>
                        <option>Partially Damaged</option>
                        <option>Water Damage</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Health Condition</label>
                    <select class="form-select form-select-solid">
                        <option>With Illness</option>
                        <option>Injured</option>
                        <option>Missing</option>
                        <option>Dead</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush shadow-sm">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Family Composition</h3>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary btn-sm">Add Member</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-7">
                            <th>Name</th>
                            <th>Birthdate</th>
                            <th>Age</th>
                            <th>Relationship</th>
                            <th>Sex</th>
                            <th>Occupation / Income</th>
                            <th>Health</th>
                            <th>Remarks Codes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input class="form-control form-control-solid" placeholder="Full name"></td>
                            <td><input class="form-control form-control-solid" type="date"></td>
                            <td><input class="form-control form-control-solid" placeholder="Auto"></td>
                            <td><input class="form-control form-control-solid"></td>
                            <td><select class="form-select form-select-solid"><option>Female</option><option>Male</option></select></td>
                            <td><input class="form-control form-control-solid"></td>
                            <td><input class="form-control form-control-solid"></td>
                            <td><input class="form-control form-control-solid" placeholder="A, B, C"></td>
                            <td><button class="btn btn-icon btn-light-danger"><i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="notice bg-light rounded border border-dashed p-5 mt-6">
                <span class="fw-bold">Codes:</span>
                <span class="text-muted">A Elderly, B Person with Disabilities, C Infant, D Pregnant Women, E Lactating Mother, F Children</span>
            </div>
        </div>
    </div>
@endsection
