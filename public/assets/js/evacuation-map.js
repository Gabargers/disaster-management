(function () {
    'use strict';

    const container = document.getElementById('evacuation-map');
    if (!container || typeof L === 'undefined') return;

    const boundaryUrl = container.dataset.boundaryUrl;
    const centersUrl = container.dataset.centersUrl;
    const map = L.map(container, { center: [14.5206, 121.0509], zoom: 13, zoomControl: true });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const boundaryLayer = L.geoJSON(null, {
        style: { color: '#7a1628', weight: 2, opacity: 0.9, fillColor: '#b64055', fillOpacity: 0.13 },
        onEachFeature: function (feature, layer) {
            const name = feature && feature.properties ? feature.properties.name : '';
            if (!name) return;
            const label = document.createElement('strong');
            label.textContent = name;
            layer.bindTooltip(label, { sticky: true, direction: 'top', className: 'barangay-tooltip' });
            layer.bindPopup(label.cloneNode(true));
            layer.on({
                mouseover: function () { layer.setStyle({ weight: 3, fillOpacity: 0.28, fillColor: '#e0526b' }); },
                mouseout: function () { boundaryLayer.resetStyle(layer); }
            });
        }
    }).addTo(map);

    const markerLayer = L.layerGroup().addTo(map);
    L.control.layers(null, {
        'Barangay Boundaries': boundaryLayer,
        'Evacuation Centers': markerLayer
    }, { collapsed: false }).addTo(map);

    const markerIcon = L.divIcon({
        className: 'evacuation-marker-wrap',
        html: '<span class="evacuation-marker"><span></span></span>',
        iconSize: [34, 42],
        iconAnchor: [17, 40],
        popupAnchor: [0, -37]
    });

    const detail = function (label, value) {
        if (value === null || value === undefined || value === '') return null;
        const row = document.createElement('div');
        row.className = 'map-popup-row';
        const key = document.createElement('span');
        key.textContent = label;
        const content = document.createElement('strong');
        content.textContent = value;
        row.append(key, content);
        return row;
    };

    const popup = function (center) {
        const panel = document.createElement('div');
        panel.className = 'map-popup';
        const title = document.createElement('div');
        title.className = 'map-popup-title';
        title.textContent = center.name || 'Evacuation Center';
        panel.appendChild(title);
        [
            detail('Address', center.address),
            detail('Barangay', center.barangay),
            detail('Capacity', center.capacity),
            detail('Status', center.status),
            detail('Assigned families', center.family_count)
        ].filter(Boolean).forEach(function (row) { panel.appendChild(row); });
        return panel;
    };

    const loadBoundaries = async function () {
        try {
            const response = await fetch(boundaryUrl, { cache: 'no-store' });
            if (!response.ok) throw new Error('Boundary request failed');
            const geojson = await response.json();
            boundaryLayer.clearLayers().addData(geojson);
            const bounds = boundaryLayer.getBounds();
            if (bounds.isValid()) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
        } catch (error) {
            console.error('Unable to load OCA BBM barangay boundaries.', error);
        }
    };

    const loadCenters = async function () {
        try {
            const response = await fetch(centersUrl, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Center request failed');
            const payload = await response.json();
            markerLayer.clearLayers();
            (payload.data || []).forEach(function (center) {
                const latitude = Number(center.latitude);
                const longitude = Number(center.longitude);
                if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return;
                L.marker([latitude, longitude], { icon: markerIcon, title: center.name || 'Evacuation Center' })
                    .bindPopup(popup(center), { maxWidth: 310 })
                    .addTo(markerLayer);
            });
        } catch (error) {
            console.error('Unable to load evacuation-center markers.', error);
        }
    };

    loadBoundaries();
    loadCenters();
    window.setInterval(loadCenters, 60000);
})();
