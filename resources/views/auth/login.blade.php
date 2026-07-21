@extends('layouts.auth.main')

@section('content')
    <div class="login-page" id="app">
        <div class="login-hero">
            <div class="login-hero__glow"></div>
            <div class="login-hero__content">
                <div class="d-flex align-items-center gap-4 mb-12">
                    <div class="login-city-logo"><img src="{{ asset('images/city_logo.webp') }}" alt="City of Taguig"></div>
                    <div>
                        <div class="text-white fw-bolder fs-2">City of Taguig</div>
                        <div class="text-white-50 fw-semibold fs-7 text-uppercase login-tracking">Disaster Management</div>
                    </div>
                </div>

                <div class="login-kicker mb-5"><span></span> Emergency response command center</div>
                <h1 class="login-headline mb-6">Ready when<br><span>every second counts.</span></h1>
                <p class="login-copy mb-10">One connected workspace for verified households, evacuation operations, assistance, and accountable payouts.</p>

                <div class="login-capabilities">
                    <div class="login-capability"><div class="login-capability__icon">01</div><div><strong>Verify</strong><span>Review and confirm affected households</span></div></div>
                    <div class="login-capability"><div class="login-capability__icon">02</div><div><strong>Coordinate</strong><span>Connect families to evacuation centers</span></div></div>
                    <div class="login-capability"><div class="login-capability__icon">03</div><div><strong>Deliver</strong><span>Track assistance through payout</span></div></div>
                </div>
            </div>
            <div class="login-hero__footer"><span class="pulse-dot"></span> Disaster response operations portal</div>
        </div>

        <div class="login-panel">
            <div class="login-panel__top"><span class="badge badge-light-danger px-4 py-3"><i class="ki-duotone ki-lock-2 text-danger fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> Authorized personnel only</span></div>

            <div class="login-card">
                @include('components.alert')
                <div class="text-center mb-9">
                    <img class="login-office-logo mb-5" src="{{ asset('images/office_logo.webp') }}" alt="Office Logo">
                    <h2 class="fw-bolder text-gray-900 fs-2x mb-2">Welcome back</h2>
                    <div class="text-gray-500 fw-semibold">Sign in to continue to the response portal</div>
                </div>

                <form class="form w-100" id="kt_sign_in_form" method="POST" action="{{ route('authenticate') }}" novalidate>
                    @csrf
                    <div class="fv-row mb-6">
                        <label for="email" class="form-label fw-bold text-gray-800">Email address</label>
                        <div class="login-input-wrap">
                            <i class="ki-duotone ki-sms fs-2 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                            <input id="email" type="email" placeholder="name@example.com" name="email" value="{{ old('email') }}" autocomplete="username" class="form-control @error('email') is-invalid @enderror" required autofocus>
                        </div>
                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="fv-row mb-5">
                        <label for="password" class="form-label fw-bold text-gray-800">Password</label>
                        <div class="login-input-wrap">
                            <i class="ki-duotone ki-key-square fs-2 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                            <input type="password" placeholder="Enter your password" name="password" id="password" autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" required>
                            <button class="login-password-toggle" type="button" id="togglePassword" tabindex="-1" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <div class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" value="1" id="remember-me" name="remember" @checked(old('remember'))>
                            <label class="form-check-label text-gray-700 fw-semibold" for="remember-me">Keep me signed in</label>
                        </div>
                        <span class="text-muted fs-8"><i class="ki-duotone ki-shield-tick text-success fs-5"><span class="path1"></span><span class="path2"></span></i> Secure session</span>
                    </div>

                    <button type="submit" id="kt_sign_in_submit" class="btn login-submit w-100">
                        <span class="indicator-label">Sign In Securely <i class="ki-duotone ki-arrow-right ms-2 fs-3"><span class="path1"></span><span class="path2"></span></i></span>
                        <span class="indicator-progress">Signing in... <span class="spinner-border spinner-border-sm ms-2"></span></span>
                    </button>
                </form>

                <div class="login-support mt-9"><i class="ki-duotone ki-information-5 fs-2 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i><div><strong>Need account assistance?</strong><span>Contact your system administrator or the Information Technology Office.</span></div></div>
            </div>

            <div class="login-copyright">© {{ now()->year }} City of Taguig · Information Technology Office</div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .login-page{--taguig-blue:#164a96;--taguig-blue-dark:#0c306c;--taguig-red:#ed1c2b;--taguig-red-dark:#b91125;--taguig-gold:#f8bd24;min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(480px,560px);position:relative;overflow:hidden;background:radial-gradient(circle at 18% 16%,rgba(237,28,43,.9) 0,transparent 31%),linear-gradient(135deg,#0b2d68 0%,#164a96 54%,#102f68 100%);isolation:isolate}
    .login-page:after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:46%;background:url('{{ asset('images/bg.webp') }}') bottom center/cover no-repeat;opacity:.17;mix-blend-mode:screen;z-index:-1;pointer-events:none}
    .login-hero{position:relative;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;padding:clamp(3rem,5vw,6rem);background:transparent;isolation:isolate}
    .login-hero:before{content:"";position:absolute;width:720px;height:720px;left:-400px;top:20%;border:1px solid rgba(255,255,255,.1);border-radius:50%;box-shadow:0 0 0 90px rgba(237,28,43,.08),0 0 0 180px rgba(248,189,36,.04);z-index:-1}
    .login-hero:after{content:"";position:absolute;top:0;right:-90px;width:250px;height:100%;background:linear-gradient(105deg,transparent 0 42%,rgba(237,28,43,.42) 43% 65%,rgba(248,189,36,.16) 66% 69%,transparent 70%);z-index:-1}
    .login-hero__glow{position:absolute;width:560px;height:560px;border-radius:50%;right:-240px;top:-220px;background:rgba(248,189,36,.19);box-shadow:0 0 110px rgba(248,189,36,.11);z-index:-1}
    .login-hero__content{max-width:760px;position:relative;z-index:1;margin:auto 0}
    .login-city-logo{width:82px;height:82px;display:grid;place-items:center;background:rgba(255,255,255,.96);border-radius:22px;padding:8px;box-shadow:0 16px 35px rgba(0,0,0,.2)}.login-city-logo img{max-width:100%;max-height:100%}
    .login-tracking{letter-spacing:.22em}.login-kicker{display:flex;align-items:center;gap:12px;color:var(--taguig-gold);font-size:.8rem;font-weight:800;text-transform:uppercase;letter-spacing:.16em}.login-kicker span{display:block;width:38px;height:3px;background:linear-gradient(90deg,var(--taguig-red),var(--taguig-gold))}
    .login-headline{font-size:clamp(3.2rem,5.3vw,6rem);line-height:.94;letter-spacing:-.06em;color:white}.login-headline span{color:var(--taguig-gold)}.login-copy{max-width:650px;color:rgba(255,255,255,.76);font-size:1.12rem;line-height:1.8}
    .login-capabilities{display:flex;align-items:stretch;max-width:760px}.login-capability{position:relative;display:flex;gap:14px;flex:1;padding:18px 20px;border-top:1px solid rgba(255,255,255,.25);border-bottom:1px solid rgba(255,255,255,.13);background:rgba(7,35,83,.38);backdrop-filter:blur(8px)}.login-capability:first-child{border-left:4px solid var(--taguig-red);border-radius:16px 0 0 16px}.login-capability:last-child{border-right:4px solid var(--taguig-gold);border-radius:0 16px 16px 0}.login-capability:not(:last-child):after{content:"";position:absolute;right:0;top:18px;bottom:18px;width:1px;background:rgba(255,255,255,.14)}.login-capability__icon{width:36px;height:36px;display:grid;place-items:center;flex:none;border-radius:50%;background:var(--taguig-gold);color:var(--taguig-blue-dark);font-size:.65rem;font-weight:900;box-shadow:0 0 0 4px rgba(248,189,36,.12)}.login-capability strong,.login-capability span{display:block}.login-capability strong{color:#fff;font-size:.86rem;margin-bottom:4px}.login-capability span{color:rgba(255,255,255,.62);font-size:.69rem;line-height:1.45}
    .login-hero__footer{position:relative;z-index:1;color:rgba(255,255,255,.55);font-size:.78rem;font-weight:600}.pulse-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#43d17b;box-shadow:0 0 0 5px rgba(67,209,123,.13);margin-right:10px}
    .login-panel{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 3.25rem;background:transparent}.login-panel__top{position:absolute;top:32px;right:52px}.login-card{position:relative;width:100%;max-width:470px;padding:48px 46px 38px;background:rgba(255,255,255,.98);border:1px solid rgba(255,255,255,.75);border-radius:28px;box-shadow:0 32px 80px rgba(3,24,63,.34);backdrop-filter:blur(18px)}.login-card:before{content:"";position:absolute;top:0;left:48px;right:48px;height:6px;border-radius:0 0 8px 8px;background:linear-gradient(90deg,var(--taguig-blue) 0 34%,var(--taguig-red) 34% 68%,var(--taguig-gold) 68%)}.login-office-logo{width:82px;height:82px;object-fit:contain;filter:drop-shadow(0 10px 16px rgba(22,74,150,.16))}
    .login-input-wrap{height:58px;display:flex;align-items:center;gap:12px;border:1px solid #dce3ef;background:#f7f9fc;border-radius:13px;padding:0 16px;transition:.2s}.login-input-wrap:focus-within{border-color:var(--taguig-blue);background:#fff;box-shadow:0 0 0 4px rgba(22,74,150,.11)}.login-input-wrap:focus-within>i{color:var(--taguig-blue)!important}.login-input-wrap .form-control{height:54px;padding:0;border:0!important;background:transparent!important;box-shadow:none!important;font-weight:600}.login-password-toggle{border:0;background:transparent;color:#71809a;padding:8px}
    .login-submit{height:56px;border-radius:13px;background:linear-gradient(120deg,var(--taguig-red),var(--taguig-red-dark));color:#fff;font-weight:800;font-size:1rem;box-shadow:0 12px 24px rgba(237,28,43,.24);transition:.2s;border-bottom:3px solid #8d1020}.login-submit:hover,.login-submit:focus{color:#fff;transform:translateY(-1px);box-shadow:0 16px 30px rgba(237,28,43,.31)}
    .login-support{display:flex;gap:13px;padding:16px 18px;background:#f1f6ff;border:1px solid #d9e5f7;border-left:4px solid var(--taguig-blue);border-radius:13px}.login-support strong,.login-support span{display:block}.login-support strong{color:var(--taguig-blue-dark);font-size:.8rem;margin-bottom:3px}.login-support span{color:#6f7f99;font-size:.72rem;line-height:1.45}.login-copyright{position:absolute;bottom:24px;color:rgba(255,255,255,.66);font-size:.7rem}
    @media(max-width:1100px){.login-page{grid-template-columns:minmax(0,1fr) minmax(440px,510px)}.login-capabilities{display:grid;grid-template-columns:1fr}.login-capability{border:1px solid rgba(255,255,255,.12)!important;border-radius:12px!important}.login-capability:nth-child(n+3){display:none}.login-headline{font-size:3.6rem}}
    @media(max-width:767px){.login-page{display:flex;flex-direction:column;overflow:auto}.login-hero{min-height:auto;padding:2rem 1.5rem 4.5rem}.login-hero__content{margin:0}.login-city-logo{width:58px;height:58px;border-radius:15px}.login-headline{font-size:2.55rem}.login-copy{font-size:.92rem;margin-bottom:0!important}.login-kicker,.login-capabilities,.login-hero__footer{display:none}.login-panel{margin-top:-2rem;padding:0 1rem 4.5rem;min-height:auto}.login-panel__top{display:none}.login-card{max-width:460px;padding:38px 24px 30px;border-radius:24px}.login-copyright{bottom:18px;text-align:center;padding:0 1rem}}
</style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/auth/password.js') }}"></script>
@endpush
