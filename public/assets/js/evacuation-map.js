(function () {
    'use strict';

    const container = document.getElementById('evacuation-map');
    if (!container || typeof L === 'undefined') return;
    const wallboard = container.dataset.wallboard === 'true';

    const loading = document.createElement('div');
    loading.className = 'map-loading-overlay';
    loading.innerHTML = '<span class="map-loading-spinner"></span><strong>Loading map...</strong>';
    container.appendChild(loading);

    const map = L.map(container, { center: [14.5206, 121.0509], zoom: 13, zoomControl: true, preferCanvas: true });
    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        updateWhenIdle: true,
        updateWhenZooming: false,
        keepBuffer: 2,
        detectRetina: false,
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
    }, { collapsed: wallboard, position: wallboard ? 'bottomleft' : 'topright' }).addTo(map);

    const markerIcon = function (center) {
        const count = Number.isFinite(Number(center.family_count)) ? Number(center.family_count) : 0;
        const label = document.createElement('span');
        label.className = 'evacuation-marker';
        const number = document.createElement('strong');
        number.textContent = count > 999 ? '999+' : String(count);
        const unit = document.createElement('small');
        unit.textContent = 'F';
        label.append(number, unit);
        return L.divIcon({
            className: 'evacuation-marker-wrap',
            html: label.outerHTML,
            iconSize: [48, 52],
            iconAnchor: [24, 49],
            popupAnchor: [0, -46]
        });
    };

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
            detail('Families', center.family_count),
            detail('Individuals', center.individual_count)
        ].filter(Boolean).forEach(function (row) { panel.appendChild(row); });
        return panel;
    };

    let spreadMarkers = [];
    const positionSpreadMarkers = function () {
        spreadMarkers.forEach(function (item) {
            if (item.total === 1) {
                item.marker.setLatLng(item.origin);
                return;
            }
            const angle = (-Math.PI / 2) + ((Math.PI * 2 * item.index) / item.total);
            const originPoint = map.project(item.origin, map.getZoom());
            const displayPoint = originPoint.add(L.point(Math.cos(angle) * 34, Math.sin(angle) * 34));
            item.marker.setLatLng(map.unproject(displayPoint, map.getZoom()));
        });
    };

    const loadBoundaries = async function () {
        try {
            const response = await fetch(container.dataset.boundaryUrl, { cache: 'force-cache' });
            if (!response.ok) throw new Error('Boundary request failed');
            const geojson = await response.json();
            boundaryLayer.clearLayers().addData(geojson);
            const bounds = boundaryLayer.getBounds();
            if (wallboard) {
                // TV view: focus on Taguig and offset the geographic center so
                // the city is centered in the visible area beside the panel.
                map.setView([14.5206, 121.0550], 13, { animate: false });
                map.panBy([175, 0], { animate: false });
            } else if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
            }
        } catch (error) {
            console.error('Unable to load OCA BBM barangay boundaries.', error);
        }
    };

    const loadCenters = async function () {
        try {
            const response = await fetch(container.dataset.centersUrl, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Center request failed');
            const payload = await response.json();
            updateWallboard(payload.data || [], payload.updated_at);
            const locations = new Map();
            (payload.data || []).forEach(function (center) {
                const latitude = Number(center.latitude);
                const longitude = Number(center.longitude);
                if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return;
                const key = latitude.toFixed(7) + ',' + longitude.toFixed(7);
                if (!locations.has(key)) locations.set(key, { latitude: latitude, longitude: longitude, centers: [] });
                locations.get(key).centers.push(center);
            });

            markerLayer.clearLayers();
            spreadMarkers = [];
            locations.forEach(function (location) {
                const origin = L.latLng(location.latitude, location.longitude);
                location.centers.forEach(function (center, index) {
                    const marker = L.marker(origin, { icon: markerIcon(center), title: center.name || 'Evacuation Center' })
                        .bindPopup(popup(center), { maxWidth: 310 })
                        .addTo(markerLayer);
                    spreadMarkers.push({ marker: marker, origin: origin, index: index, total: location.centers.length });
                });
            });
            positionSpreadMarkers();
        } catch (error) {
            console.error('Unable to load evacuation-center markers.', error);
        }
    };

    const setText = function (id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    const updateWallboard = function (centers, updatedAt) {
        if (!wallboard) return;
        const totalFamilies = centers.reduce(function (total, center) { return total + Number(center.family_count || 0); }, 0);
        const totalIndividuals = centers.reduce(function (total, center) { return total + Number(center.individual_count || 0); }, 0);
        const totalCapacity = centers.reduce(function (total, center) { return total + Number(center.capacity || 0); }, 0);
        const occupied = centers.filter(function (center) { return Number(center.family_count || 0) > 0; });
        const utilization = totalCapacity > 0 ? Math.min(100, Math.round((totalFamilies / totalCapacity) * 100)) : 0;
        setText('wallboardCenters', centers.length.toLocaleString());
        setText('wallboardOccupied', occupied.length.toLocaleString());
        setText('wallboardFamilies', totalFamilies.toLocaleString());
        setText('wallboardIndividuals', totalIndividuals.toLocaleString());
        setText('wallboardUtilization', utilization + '%');
        const bar = document.getElementById('wallboardUtilizationBar');
        if (bar) bar.style.width = utilization + '%';
        const updated = updatedAt ? new Date(updatedAt) : new Date();
        setText('wallboardUpdated', 'Updated ' + updated.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));

        const list = document.getElementById('wallboardTopCenters');
        if (!list) return;
        list.replaceChildren();
        occupied.sort(function (a, b) { return Number(b.family_count || 0) - Number(a.family_count || 0); }).slice(0, 6).forEach(function (center, index) {
            const item = document.createElement('div');
            item.className = 'top-center';
            const rank = document.createElement('span');
            rank.className = 'top-center-rank';
            rank.textContent = index + 1;
            const details = document.createElement('div');
            const name = document.createElement('div');
            name.className = 'top-center-name';
            name.textContent = center.name;
            const address = document.createElement('span');
            address.className = 'top-center-address';
            address.textContent = center.barangay || center.address || 'Taguig City';
            details.append(name, address);
            const count = document.createElement('div');
            count.className = 'top-center-count';
            const value = document.createElement('strong');
            value.textContent = Number(center.family_count || 0).toLocaleString();
            const unit = document.createElement('span');
            unit.textContent = 'families';
            count.append(value, unit);
            item.append(rank, details, count);
            list.appendChild(item);
        });
        if (!list.children.length) {
            const empty = document.createElement('div');
            empty.className = 'empty-centers';
            empty.textContent = 'No families are currently assigned.';
            list.appendChild(empty);
        }
    };

    loadBoundaries();
    loadCenters();
    const finishLoading = function () {
        window.requestAnimationFrame(function () {
            map.invalidateSize(false);
            loading.classList.add('is-hidden');
            window.setTimeout(function () { loading.remove(); }, 250);
        });
    };
    tiles.once('load', finishLoading);
    window.setTimeout(finishLoading, 3500);
    window.addEventListener('resize', function () { map.invalidateSize(false); });
    map.on('zoomend', positionSpreadMarkers);
    window.setInterval(loadCenters, 5000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) loadCenters();
    });
    const updateClock = function () {
        if (!wallboard) return;
        const now = new Date();
        setText('wallboardTime', now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        setText('wallboardDate', now.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }));
    };
    updateClock();
    window.setInterval(updateClock, 1000);
})();
