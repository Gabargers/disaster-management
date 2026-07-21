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
                    $isSuperAdmin = auth()->user()->hasRole('superadmin');
                    $canManageAccounts = auth()->user()->hasAnyRole(['admin', 'superadmin']);

                    $canBarangay =
                        $isSuperAdmin ||
                        auth()
                            ->user()
                            ->hasAnyPermission(['view barangay table', 'store barangay', 'update barangay', 'delete barangay']);

                    $barangayRoute = \Illuminate\Support\Facades\Route::has($role . '.barangay.index') ? $role . '.barangay.index' : null;
                    $cmsRoutes = $barangayRoute ? ['barangay.index'] : [];

                    $cmsOpen = collect($cmsRoutes)->map(fn($r) => "$role.$r")->contains(fn($routeName) => request()->routeIs($routeName));

                    $canCms = $canBarangay && $barangayRoute;
                    $dashboardRoute = \Illuminate\Support\Facades\Route::has($role . '.dashboard') ? $role . '.dashboard' : 'dashboard';
                    $disasterModules = [
                        ['route' => 'disaster.tciss.index', 'label' => 'TCISS', 'icon' => 'ki-magnifier', 'permission' => 'manage tciss masterlist'],
                        ['route' => 'disaster.payroll.index', 'label' => 'Payroll', 'icon' => 'ki-dollar', 'permission' => 'prepare payroll list'],
                        ['route' => 'disaster.payouts.index', 'label' => 'Evacuation Center', 'icon' => 'ki-geolocation', 'permission' => 'manage payout schedules'],
                        ['route' => 'disaster.reports.index', 'label' => 'Reports', 'icon' => 'ki-document', 'permission' => 'view disaster reports'],
                    ];
                    $visibleDisasterModules = collect($disasterModules)
                        ->filter(fn ($module) => auth()->user()->can($module['permission']));
                @endphp

                <div class="menu-item py-2">
                    <a href="{{ route($dashboardRoute) }}"
                        class="menu-link menu-center flex-column {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}"
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

                @foreach ($visibleDisasterModules as $module)
                    <div class="menu-item py-2">
                        <a href="{{ route($module['route']) }}"
                            data-sidebar-route="{{ $module['route'] }}"
                            class="menu-link menu-center flex-column {{ request()->routeIs($module['route']) ? 'active' : '' }}"
                            style="gap: 2px;">
                            <span class="menu-icon me-0">
                                <i class="ki-duotone {{ $module['icon'] }} fs-2x">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                            <span class="menu-title fs-7 text-center">
                                {{ $module['label'] }}
                            </span>
                        </a>
                    </div>
                @endforeach

                @if ($canManageAccounts)
                    <div class="menu-item py-2">
                        <a href="{{ route('accounts.index') }}"
                            data-sidebar-route="accounts.index"
                            class="menu-link menu-center flex-column {{ request()->routeIs('accounts.*') ? 'active' : '' }}"
                            style="gap: 2px;">
                            <span class="menu-icon me-0">
                                <i class="ki-duotone ki-people fs-2x">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    <span class="path4"></span><span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title fs-7 text-center">Accounts</span>
                        </a>
                    </div>
                @endif

                @if ($isSuperAdmin)
                    <div class="menu-item py-2">
                        <a href="{{ route('activity-logs.index') }}" data-sidebar-route="activity-logs.index"
                            class="menu-link menu-center flex-column {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" style="gap: 2px;">
                            <span class="menu-icon me-0"><i class="ki-duotone ki-document fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span>
                            <span class="menu-title fs-7 text-center">Activity Logs</span>
                        </a>
                    </div>
                @endif

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
                                @if ($canBarangay)
                                    <a class="menu-link {{ request()->routeIs($role . '.barangay.*') ? 'active' : '' }}"
                                        href="{{ route($barangayRoute) }}">
                                        <span class="menu-bullet">
                                            <i class="ki-duotone ki-geolocation fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Barangay</span>
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
