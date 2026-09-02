<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evacuation Map Display</title>
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/evacuation-map.css') }}">
    <link rel="icon" type="image/webp" href="{{ asset('images/CSWDO.webp') }}">
    <style>
        html,body{width:100%;height:100%;margin:0;overflow:hidden;background:#eef4fb}#evacuation-map{width:100vw;height:100vh;min-height:100vh}
        .dashboard-back{position:fixed;z-index:10;top:1rem;left:1rem;display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .8rem;border:1px solid rgba(23,33,60,.1);border-radius:.65rem;color:#17213c;background:rgba(255,255,255,.92);box-shadow:0 5px 18px rgba(24,28,50,.1);font-size:.78rem;font-weight:700;text-decoration:none;backdrop-filter:blur(8px);transition:transform .15s ease,box-shadow .15s ease}
        .dashboard-back:hover{color:#1877c9;transform:translateY(-1px);box-shadow:0 8px 22px rgba(24,28,50,.14)}
    </style>
</head>
<body>
    <a href="{{ route('dashboard') }}" class="dashboard-back" aria-label="Back to Dashboard">
        <i class="ki-duotone ki-arrow-left fs-5"><span class="path1"></span><span class="path2"></span></i>
        Back to Dashboard
    </a>
    <main id="evacuation-map"
        data-boundary-url="{{ asset('map/oca-bbm-barangays.geojson') }}"
        data-centers-url="{{ route('evacuation-map.centers') }}"
        aria-label="Fullscreen Taguig barangay and evacuation center map"></main>
    <script src="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/evacuation-map.js') }}"></script>
</body>
</html>
