<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}" type="text/css">
    <link rel="icon" type="image/png" href="{{ asset('images/office_logo.webp') }}">

    <style>
        [x-cloak] {
            display: none !important;
        }

        .auth-container {
            min-height: 100vh;
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

<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center">

    <style>
        body {
            background-image: url('{{ asset('images/bg.webp') }}');
        }

        [data-bs-theme="dark"] body {
            background-image: url('{{ asset('images/bg.webp') }}');
        }
    </style>

    <div class="d-flex flex-column flex-root auth-container">
        @yield('content')
    </div>

    <script>
        var hostUrl = "{{ asset('assets') }}/";
    </script>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    @include('components.loading')
    <script src="{{ asset('assets/js/loader/kt-page-loader.js') }}"></script>
    
    @stack('modals')
    @stack('scripts')
    @stack('styles')
</body>

</html>
