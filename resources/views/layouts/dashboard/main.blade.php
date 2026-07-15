<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/header-bg.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/office_logo.webp') }}">

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Keep page titles readable over the light and detailed header artwork. */
        #kt_header .page-title h1 {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(16, 42, 86, 0.12);
            border-radius: 0.65rem;
            padding: 0.65rem 1rem;
            box-shadow: 0 2px 10px rgba(24, 28, 50, 0.12);
        }

        #kt_header .page-title h1 > span,
        #kt_header .page-title h1 > small {
            color: #102a56 !important;
            text-shadow: none;
        }
    </style>

    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                themeMode = localStorage.getItem("data-bs-theme") ?? defaultThemeMode;
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
</head>

<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed aside-fixed aside-secondary-disabled">

    <style>
        body {
            /* background-image: url('{{ asset('images/bg.webp') }}'); */
        }

        [data-bs-theme="dark"] body {
            /* background-image: url('{{ asset('images/bg.webp') }}'); */
        }
    </style>

    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            @include('layouts.dashboard.sidebar')
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                @include('layouts.dashboard.header')
                <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                    <div class="p-10" id="kt_content_container">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var hostUrl = "{{ asset('assets') }}/";
    </script>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="https://kit.fontawesome.com/4f2d7302b1.js" crossorigin="anonymous"></script>
    @include('components.loading')
    <script src="{{ asset('assets/js/loader/kt-page-loader.js') }}"></script>

    @stack('modals')
    @stack('scripts')
    @stack('styles')
</body>

</html>
