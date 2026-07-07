@extends('layouts.auth.main')

@section('content')
    @include('components.alert')

    <div id="app" class="d-flex flex-column flex-lg-row flex-column-fluid">
        <div class="d-flex flex-lg-row-fluid align-items-center">
            <div class="d-flex flex-column flex-center p-10 p-lg-20 w-100">
                <div class="d-flex align-items-center gap-5 mb-12">
                    <img class="h-75px h-lg-100px" src="{{ asset('images/city_logo.webp') }}" alt="City Logo" />
                    <div class="border-start border-3 border-danger ps-5">
                        <div class="text-white fw-bold fs-2x lh-sm">{{ config('app.name') }}</div>
                        <div class="text-white-50 fw-semibold fs-5">Disaster Management Assistance System</div>
                    </div>
                </div>

                <div class="mw-700px text-center text-lg-start">
                    <h1 class="text-white fw-bolder fs-3x fs-lg-4x mb-6">
                        Coordinated response starts here.
                    </h1>
                    <div class="text-white-75 fs-4 fw-semibold lh-lg mb-10">
                        Secure access for incident monitoring, assistance coordination, resource tracking,
                        and emergency response operations.
                    </div>

                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 p-5 h-100">
                                <i class="ki-duotone ki-shield-tick fs-2x text-danger mb-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-white fw-bold fs-6 mb-1">Protected Access</div>
                                <div class="text-white-50 fs-7">Rate-limited authentication with session protection.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 p-5 h-100">
                                <i class="ki-duotone ki-map fs-2x text-warning mb-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="text-white fw-bold fs-6 mb-1">Field Ready</div>
                                <div class="text-white-50 fs-7">Built for teams coordinating urgent assistance.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 p-5 h-100">
                                <i class="ki-duotone ki-notification-status fs-2x text-info mb-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                                <div class="text-white fw-bold fs-6 mb-1">Fast Triage</div>
                                <div class="text-white-50 fs-7">Designed for clear intake and response workflows.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-8 p-lg-12">
            <div class="bg-body d-flex flex-column flex-center rounded-3 w-md-575px p-10 shadow-lg">
                <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                    <div class="d-flex flex-center flex-column flex-column-fluid pb-10 pb-lg-15">
                        <form class="form w-100" id="kt_sign_in_form" method="POST" action="{{ route('authenticate') }}" novalidate>
                            @csrf

                            <div class="text-center mb-10">
                                <img class="h-90px mb-7" src="{{ asset('images/office_logo.webp') }}" alt="Office Logo" />
                                <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
                                <div class="text-gray-600 fw-semibold fs-6">
                                    Use your authorized response account to continue.
                                </div>
                            </div>

                            <div class="fv-row mb-8">
                                <label for="email" class="form-label fw-bold text-gray-900">Email address</label>
                                <input id="email" type="email" placeholder="name@example.com" name="email" value="{{ old('email') }}"
                                    autocomplete="username"
                                    class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror" required
                                    autofocus />
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fv-row mb-4">
                                <label for="password" class="form-label fw-bold text-gray-900">Password</label>
                                <div class="input-group input-group-lg input-group-solid">
                                    <input type="password" placeholder="Enter your password" name="password" id="password"
                                        autocomplete="current-password"
                                        class="form-control form-control-solid @error('password') is-invalid @enderror" required />
                                    <button class="btn btn-icon btn-light" type="button" id="togglePassword" tabindex="-1"
                                        aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <div class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" id="remember-me" name="remember"
                                        @checked(old('remember')) />
                                    <label class="form-check-label text-gray-700" for="remember-me">Remember me</label>
                                </div>
                                <span class="text-muted fs-7">Password reset coming soon</span>
                            </div>

                            <div class="d-grid mb-8">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-danger">
                                    <span class="indicator-label">Sign In</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>

                            <div class="separator separator-content my-10">
                                <span class="w-175px text-gray-500 fw-semibold fs-7">Authorized personnel only</span>
                            </div>

                            <div class="text-center">
                                <span class="text-gray-600 fw-semibold fs-7">
                                    Copyright {{ now()->year }} Information Technology Office - City of Taguig
                                </span>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex flex-stack">
                        <div></div>
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <a href="#" target="_blank">Terms</a>
                            <a href="#" target="_blank">Contact</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/auth/password.js') }}"></script>
@endpush
