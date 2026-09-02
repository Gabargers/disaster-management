<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evacuation Map Display</title>
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/evacuation-map.css') }}?v=5">
    <link rel="icon" type="image/webp" href="{{ asset('images/CSWDO.webp') }}">
    <style>
        html,body{width:100%;height:100%;margin:0;overflow:hidden;background:#0d1728}#evacuation-map{width:100vw;height:100vh;min-height:100vh}
        .wallboard-header{position:fixed;z-index:1100;top:18px;left:18px;right:18px;display:flex;height:76px;padding:0 18px;align-items:center;justify-content:space-between;border:1px solid rgba(255,255,255,.18);border-radius:18px;color:#fff;background:linear-gradient(110deg,rgba(65,9,20,.96),rgba(128,25,44,.94));box-shadow:0 14px 38px rgba(20,12,17,.28);backdrop-filter:blur(14px)}
        .wallboard-brand{display:flex;align-items:center;gap:14px}.wallboard-logo{display:grid;width:50px;height:50px;place-items:center;border-radius:14px;background:#fff;box-shadow:0 5px 15px rgba(0,0,0,.15)}.wallboard-logo img{max-width:40px;max-height:40px}.wallboard-title small{display:block;margin-bottom:2px;color:rgba(255,255,255,.7);font-size:10px;font-weight:800;letter-spacing:.14em}.wallboard-title h1{margin:0;color:#fff;font-size:21px;font-weight:800}.wallboard-title p{margin:2px 0 0;color:rgba(255,255,255,.72);font-size:11px}
        .wallboard-status{display:flex;align-items:center;gap:18px}.live-status{display:flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid rgba(103,235,165,.28);border-radius:10px;color:#9bffc9;background:rgba(13,80,47,.34);font-size:11px;font-weight:800;letter-spacing:.08em}.live-status i{width:8px;height:8px;border-radius:50%;background:#52e99a;box-shadow:0 0 0 5px rgba(82,233,154,.13)}.wallboard-clock{text-align:right}.wallboard-clock strong{display:block;font-size:21px;line-height:1;font-variant-numeric:tabular-nums}.wallboard-clock span{display:block;margin-top:5px;color:rgba(255,255,255,.65);font-size:10px}.dashboard-back{display:inline-flex;align-items:center;gap:5px;padding:9px 11px;border:1px solid rgba(255,255,255,.18);border-radius:10px;color:#fff;background:rgba(255,255,255,.1);font-size:11px;font-weight:700;text-decoration:none}.dashboard-back:hover{color:#fff;background:rgba(255,255,255,.18)}
        .executive-panel{position:fixed;z-index:1050;top:112px;right:18px;bottom:18px;width:350px;padding:18px;border:1px solid rgba(255,255,255,.72);border-radius:18px;background:rgba(255,255,255,.94);box-shadow:0 16px 45px rgba(22,34,54,.2);backdrop-filter:blur(16px);overflow:hidden}.panel-kicker{color:#9b263e;font-size:10px;font-weight:800;letter-spacing:.12em}.panel-heading{margin:3px 0 14px;color:#162039;font-size:18px;font-weight:800}.kpi-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.kpi-card{padding:12px;border:1px solid #e9edf4;border-radius:13px;background:#fff}.kpi-card.accent{color:#fff;border-color:#72172a;background:linear-gradient(145deg,#591020,#9b2940)}.kpi-label{display:block;color:#8992a7;font-size:9px;font-weight:800;letter-spacing:.07em;text-transform:uppercase}.kpi-card.accent .kpi-label{color:rgba(255,255,255,.68)}.kpi-value{display:block;margin-top:4px;color:#182238;font-size:25px;line-height:1;font-weight:850;letter-spacing:-.04em}.kpi-card.accent .kpi-value{color:#fff}.kpi-note{display:block;margin-top:5px;color:#9aa3b5;font-size:9px}.kpi-card.accent .kpi-note{color:rgba(255,255,255,.65)}
        .occupancy{margin:13px 0;padding:12px;border-radius:13px;background:#f4f7fb}.occupancy-top{display:flex;align-items:center;justify-content:space-between;color:#596276;font-size:10px;font-weight:700}.occupancy-top strong{color:#17213a;font-size:12px}.occupancy-track{height:7px;margin-top:8px;border-radius:10px;background:#e0e6ee;overflow:hidden}.occupancy-bar{display:block;width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,#39ba77,#ffc23d,#e94e68);transition:width .4s ease}
        .top-centers-title{display:flex;margin:4px 0 7px;align-items:center;justify-content:space-between}.top-centers-title strong{color:#17213a;font-size:12px}.top-centers-title span{color:#9aa3b5;font-size:9px}.top-centers{display:flex;max-height:calc(100vh - 525px);min-height:80px;flex-direction:column;overflow:hidden}.top-center{display:grid;padding:9px 0;grid-template-columns:25px minmax(0,1fr) auto;gap:8px;align-items:center;border-top:1px dashed #e0e5ed}.top-center-rank{display:grid;width:24px;height:24px;place-items:center;border-radius:8px;color:#8b1e34;background:#fff0f3;font-size:9px;font-weight:800}.top-center-name{overflow:hidden;color:#333d52;font-size:10px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}.top-center-address{display:block;margin-top:2px;overflow:hidden;color:#9aa3b5;font-size:8px;font-weight:500;text-overflow:ellipsis;white-space:nowrap}.top-center-count{text-align:right}.top-center-count strong{display:block;color:#8b1e34;font-size:15px;line-height:1}.top-center-count span{color:#9aa3b5;font-size:8px}.empty-centers{padding:18px;text-align:center;color:#9aa3b5;font-size:10px}
        .updated-status{position:absolute;right:18px;bottom:14px;left:18px;display:flex;padding-top:10px;align-items:center;justify-content:space-between;border-top:1px solid #e6eaf0;color:#9aa3b5;font-size:9px}.updated-status strong{color:#34875d}.mayor-wallboard .leaflet-top.leaflet-left{top:104px}.mayor-wallboard .leaflet-control-zoom{border:0!important;box-shadow:0 8px 24px rgba(26,38,59,.18)!important}.mayor-wallboard .leaflet-bottom.leaflet-left{left:18px;bottom:12px}.mayor-wallboard .leaflet-control-layers{margin:0!important}
        @media(max-width:900px){.wallboard-header{height:66px}.wallboard-title p,.wallboard-clock span{display:none}.executive-panel{top:auto;left:12px;right:12px;bottom:12px;width:auto;height:190px}.kpi-grid{grid-template-columns:repeat(4,1fr)}.occupancy,.top-centers-title,.top-centers,.updated-status{display:none}.wallboard-status{gap:8px}.mayor-wallboard .leaflet-top.leaflet-left{top:92px}}
    </style>
</head>
<body class="mayor-wallboard">
    <header class="wallboard-header">
        <div class="wallboard-brand">
            <div class="wallboard-logo"><img src="{{ asset('images/city_logo.webp') }}" alt="Taguig City"></div>
            <div class="wallboard-title"><small>CITY OF TAGUIG</small><h1>Evacuation Operations Monitor</h1><p>Real-time citywide evacuation center monitoring</p></div>
        </div>
        <div class="wallboard-status">
            <div class="live-status"><i></i> LIVE DATA</div>
            <div class="wallboard-clock"><strong id="wallboardTime">--:--:--</strong><span id="wallboardDate">Loading date</span></div>
            <a href="{{ route('dashboard') }}" class="dashboard-back" aria-label="Back to Dashboard"><i class="ki-duotone ki-arrow-left fs-5"><span class="path1"></span><span class="path2"></span></i>Dashboard</a>
        </div>
    </header>
    <aside class="executive-panel" aria-label="Evacuation monitoring summary">
        <div class="panel-kicker">EXECUTIVE SUMMARY</div><h2 class="panel-heading">Citywide Situation</h2>
        <div class="kpi-grid">
            <div class="kpi-card"><span class="kpi-label">Centers</span><strong class="kpi-value" id="wallboardCenters">0</strong><span class="kpi-note">mapped locations</span></div>
            <div class="kpi-card"><span class="kpi-label">Occupied</span><strong class="kpi-value" id="wallboardOccupied">0</strong><span class="kpi-note">with families</span></div>
            <div class="kpi-card accent"><span class="kpi-label">Families</span><strong class="kpi-value" id="wallboardFamilies">0</strong><span class="kpi-note">currently assigned</span></div>
            <div class="kpi-card accent"><span class="kpi-label">Individuals</span><strong class="kpi-value" id="wallboardIndividuals">0</strong><span class="kpi-note">monitored people</span></div>
        </div>
        <div class="occupancy"><div class="occupancy-top"><span>Citywide capacity usage</span><strong id="wallboardUtilization">0%</strong></div><div class="occupancy-track"><span class="occupancy-bar" id="wallboardUtilizationBar"></span></div></div>
        <div class="top-centers-title"><strong>Centers with Evacuees</strong><span>Highest family count</span></div>
        <div class="top-centers" id="wallboardTopCenters"><div class="empty-centers">Waiting for live center data...</div></div>
        <div class="updated-status"><span>Automatic refresh every 5 seconds</span><strong id="wallboardUpdated">Connecting...</strong></div>
    </aside>
    <main id="evacuation-map"
        data-wallboard="true"
        data-boundary-url="{{ asset('map/oca-bbm-barangays.geojson') }}"
        data-centers-url="{{ route('evacuation-map.centers') }}"
        aria-label="Fullscreen Taguig barangay and evacuation center map"></main>
    <script src="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/evacuation-map.js') }}?v=6"></script>
</body>
</html>
