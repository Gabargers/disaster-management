@extends('layouts.auth.main')
@section('content')
    @include('components.alert')

    <div id="app" class="d-flex flex-column flex-lg-row flex-column-fluid">

        <div class="d-flex flex-lg-row-fluid align-items-center">
            <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">

                <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="{{ asset('images/office_logo.webp') }}"
                    alt="Office Logo" />
                <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="{{ asset('images/office_logo.webp') }}"
                    alt="Office Logo" />

                <div class="card bg-body bg-opacity-75 shadow-sm border-0 rounded-4 mw-700px w-100">
                    <div class="card-body p-6 p-lg-8 text-center">

                        <h1 class="text-gray-900 fs-2qx fw-bold mb-4">
                            Transforming Excellence into Purpose
                        </h1>

                        <div class="text-gray-800 fs-base fw-semibold">
                            Owning a pet is not a right but rather a privilege. Privilege that comes with certain
                            responsibilities!
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
            <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10 card-maroon rounded-4 shadow-lg">
                <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                    <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">

                        <form class="form w-100" id="tcu_sign_in_form" method="POST" action="{{ route('authenticate') }}" novalidate>
                            @csrf

                            <div class="text-center mb-11">
                                <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
                                <div class="text-gray-700 fw-semibold fs-6">
                                    Welcome to City of Taguig {{ config('app.name') }}.
                                    <br />Please sign in to continue.
                                </div>
                            </div>

                            <div class="fv-row mb-8">
                                <input type="email" placeholder="Email" name="email" value="{{ old('email') }}" autocomplete="username"
                                    class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror" required
                                    autofocus />
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fv-row mb-3">
                                <div class="input-group input-group-lg input-group-solid">
                                    <input type="password" placeholder="Password" name="password" id="password" autocomplete="current-password"
                                        class="form-control form-control-solid @error('password') is-invalid @enderror" required />
                                    <button class="btn btn-icon btn-secondary" type="button" id="togglePassword" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <div class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" id="remember-me" name="remember">
                                    <label class="form-check-label text-gray-700" for="remember-me">Remember me</label>
                                </div>
                                <a href="#" class="link-primary">Forgot Password?</a>
                            </div>

                            <div class="d-grid mb-10">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-danger">
                                    <span class="indicator-label">Sign In</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>

                            <div class="text-center mt-10">
                                <span class="text-gray-600 fw-semibold fs-7">
                                    © 2025 Information Technology Office — City of Taguig
                                </span>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex flex-stack">
                        <div></div>
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <a href="#" target="_blank">Terms</a>
                            <a href="#" target="_blank">Contact Us</a>
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
