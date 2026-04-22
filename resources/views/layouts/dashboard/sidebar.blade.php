<div id="kt_aside" class="aside" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true" data-kt-drawer-width="auto" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_toggle">

    <div class="aside-logo flex-column-auto pt-10 pt-lg-20" id="kt_aside_logo">
        <a href="#">
            <img alt="Logo" src="{{ asset('images/city_logo.webp') }}" class="h-100px" />
        </a>
    </div>

    <div class="aside-menu flex-column-fluid pt-0 pb-7 py-lg-10" id="kt_aside_menu">
        <div id="kt_aside_menu_wrapper" class="w-100 hover-scroll-y scroll-ms d-flex" data-kt-scroll="true" data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside, #kt_aside_menu"
            data-kt-scroll-offset="0">

            <div id="kt_aside_menu"
                class="menu menu-column menu-title-gray-600 menu-state-primary
                        menu-state-icon-primary menu-state-bullet-primary
                        menu-icon-gray-400 menu-arrow-gray-400 fw-semibold fs-6 my-auto"
                data-kt-menu="true">

                @php
                    $role = Auth::user()->getRoleNames()->first();

                    $canMedicine = auth()
                        ->user()
                        ->hasAnyPermission(['view medicine table', 'store medicine', 'update medicine', 'delete medicine']);

                    $canSpecies = auth()
                        ->user()
                        ->hasAnyPermission(['view pet species table', 'store pet species', 'update pet species', 'delete pet species']);

                    $canBreed = auth()
                        ->user()
                        ->hasAnyPermission(['view pet breed table', 'store pet breed', 'update pet breed', 'delete pet breed']);

                    $canTeam = auth()
                        ->user()
                        ->hasAnyPermission(['view team table', 'store team', 'update team', 'delete team']);

                    $canViolation = auth()
                        ->user()
                        ->hasAnyPermission(['view violation table', 'store violation', 'update violation', 'delete violation']);

                    $canService = auth()
                        ->user()
                        ->hasAnyPermission(['view service table', 'store service', 'update service', 'delete service']);

                    $canAppointmentSchedule = auth()
                        ->user()
                        ->hasAnyPermission([
                            'view appointment schedule table',
                            'store appointment schedule',
                            'update appointment schedule',
                            'delete appointment schedule',
                        ]);

                    $cmsRoutes = ['medicine.index', 'species.index', 'breed.index', 'team.index', 'violation.index', 'service.index'];

                    $cmsOpen = collect($cmsRoutes)->map(fn($r) => "$role.$r")->contains(fn($routeName) => request()->routeIs($routeName));

                    $canCms = $canMedicine || $canSpecies || $canBreed || $canTeam || $canViolation || $canService;
                @endphp

                <div class="menu-item py-2">
                    <a href="{{ route($role . '.dashboard') }}"
                        class="menu-link menu-center flex-column {{ request()->routeIs($role . '.dashboard') ? 'active' : '' }}"
                        style="gap: 2px;">

                        <span class="menu-icon me-0">
                            <i class="ki-duotone ki-home-2 fs-2x">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>

                        <span class="menu-title fs-7 text-center">
                            Dashboard
                        </span>
                    </a>
                </div>

                @can('view pet table')
                    <div class="menu-item py-2">
                        <a href="{{ route($role . '.pet.index') }}"
                            class="menu-link menu-center flex-column {{ request()->routeIs($role . '.pet.*') ? 'active' : '' }}"
                            style="gap: 2px;">

                            <span class="menu-icon me-0">
                                <i class="ki-duotone ki-abstract-26 fs-2x">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>

                            <span class="menu-title fs-7 text-center">
                                Pets
                            </span>
                        </a>
                    </div>
                @endcan
                
                @can('view appointment schedule table')
                    <div class="menu-item py-2">
                        <a href="{{ route($role . '.schedule.index') }}"
                            class="menu-link menu-center flex-column {{ request()->routeIs($role . '.schedule.*') ? 'active' : '' }}"
                            style="gap: 2px;">

                            <span class="menu-icon me-0">
                                <i class="ki-duotone ki-calendar-8 fs-2x">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>

                            <span class="menu-title fs-7 text-center">
                                Schedule
                            </span>
                        </a>
                    </div>
                @endcan

                @if ($canCms)
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
                        class="menu-item py-2 {{ $cmsOpen ? 'show here' : '' }}">

                        <span class="menu-link menu-center flex-column {{ $cmsOpen ? 'active' : '' }}" style="gap: 2px;">

                            <span class="menu-icon me-0">
                                <i class="ki-duotone ki-setting-3 fs-2x">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>

                            <span class="menu-title fs-7 text-center">
                                CMS
                            </span>
                        </span>

                        <div class="menu-sub menu-sub-dropdown px-2 py-4 w-250px mh-75 overflow-auto">
                            <div class="menu-item">
                                <div class="menu-content">
                                    <span class="menu-section fs-5 fw-bolder ps-1 py-1">CMS</span>
                                </div>
                            </div>

                            <div class="menu-item">
                                @if ($canMedicine)
                                    <a class="menu-link {{ request()->routeIs($role . '.medicine.*') ? 'active' : '' }}"
                                        href="{{ route($role . '.medicine.index') }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-capsule fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Medicine</span>
                                    </a>
                                @endif

                                @if ($canSpecies)
                                    <a class="menu-link {{ request()->routeIs($role . '.species.*') ? 'active' : '' }}"
                                        href="{{ route($role . '.species.index') }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-abstract-26 fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Species</span>
                                    </a>
                                @endif

                                @if ($canBreed)
                                    <a class="menu-link {{ request()->routeIs($role . '.breed.*') ? 'active' : '' }}"
                                        href="{{ route($role . '.breed.index') }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-category fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Breed</span>
                                    </a>
                                @endif

                                @if ($canTeam)
                                    <a class="menu-link {{ request()->routeIs($role . '.team.*') ? 'active' : '' }}"
                                        href="{{ route($role . '.team.index') }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-people fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Team</span>
                                    </a>
                                @endif

                                @if ($canViolation)
                                    <a class="menu-link {{ request()->routeIs($role . '.violation.*') ? 'active' : '' }}"
                                        href="{{ route($role . '.violation.index') }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-shield-cross fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Violation</span>
                                    </a>
                                @endif

                                @if ($canService)
                                    <a class="menu-link {{ request()->routeIs($role . '.service.*') ? 'active' : '' }}"
                                        href="{{ route($role . '.service.index') }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-notepad-edit fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Service</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="aside-footer flex-column-auto pb-5 pb-lg-10" id="kt_aside_footer">
        <div class="d-flex flex-center w-100 scroll-px" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-dismiss="click"
            title="Quick actions">

            <button type="button" class="btn btn-custom" data-kt-menu-trigger="click" data-kt-menu-overflow="true"
                data-kt-menu-placement="top-start">
                <i class="ki-duotone ki-entrance-left fs-2x">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </button>

            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded
                        menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px"
                data-kt-menu="true">

                <div class="menu-item px-3">
                    <div class="menu-content fs-6 text-dark fw-bold px-3 py-4">
                        Quick Actions
                    </div>
                </div>

                <div class="separator mb-3 opacity-75"></div>

                <div class="menu-item px-3">
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button type="submit" class="menu-link px-3 btn btn-light btn-sm w-100 text-start">
                            Logout
                        </button>
                    </form>
                </div>

                <div class="separator mt-3 opacity-75"></div>
            </div>
        </div>
    </div>
</div>
