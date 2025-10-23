<?php // path: public/dashboard.php
require_once __DIR__ . '/../app/auth.php';

require_login();
$user = current_user();
$config = app_config();
$hasSubscription = user_has_active_subscription($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — SOCKET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-dark text-light min-vh-100 d-flex flex-column">
<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">SOCKET</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="map.php">Map</a></li>
                <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin/stations.php">Admin</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-grow-1">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="card bg-black border border-secondary h-100">
                    <div class="card-body">
                        <h1 class="h4 mb-3">Subscription status</h1>
                        <?php if ($hasSubscription): ?>
                            <p class="mb-2 text-success">Premium active</p>
                            <p class="text-secondary mb-4">Expires on <strong><?= sanitize(date('d M Y', strtotime($user['premium_until']))); ?></strong>.</p>
                        <?php else: ?>
                            <p class="mb-2 text-warning">Premium inactive</p>
                            <p class="text-secondary mb-4">Enjoy <?= $config['trial_days']; ?> days free, then upgrade for <?= $config['subscription_days']; ?> days of premium access at <?= $config['subscription_amount']; ?> ETB.</p>
                        <?php endif; ?>
                        <button id="upgradeButton" class="btn btn-primary btn-lg w-100" <?= $hasSubscription ? 'disabled' : ''; ?>><?= $hasSubscription ? 'Premium Active' : 'Upgrade with Chapa'; ?></button>
                        <div id="paymentMessage" class="small text-secondary mt-3"></div>
                        <hr class="border-secondary my-4">
                        <h2 class="h5">Quick stats</h2>
                        <ul class="list-unstyled text-secondary" id="stationStats">
                            <li>Loading station data…</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card bg-black border border-secondary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Live map</h2>
                            <div class="d-flex gap-2 flex-wrap">
                                <select id="connectorFilter" class="form-select form-select-sm bg-dark text-light border-secondary">
                                    <option value="">All connectors</option>
                                    <option value="CCS">CCS</option>
                                    <option value="Type2">Type 2</option>
                                    <option value="CHAdeMO">CHAdeMO</option>
                                </select>
                                <select id="statusFilter" class="form-select form-select-sm bg-dark text-light border-secondary">
                                    <option value="">All statuses</option>
                                    <option value="available">Available</option>
                                    <option value="busy">Busy</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                        </div>
                        <div id="dashboardMap" class="rounded" style="height: 400px;"></div>
                        <div class="mt-3">
                            <button class="btn btn-outline-light btn-sm" id="nearbyButton">Find chargers within 10 km</button>
                            <div id="nearbyList" class="mt-3 text-secondary small"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<footer class="bg-black text-secondary py-3 text-center small">
    <p class="mb-0">&copy; <?= date('Y'); ?> SOCKET.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('dashboardMap').setView([8.9806, 38.7578], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const markers = [];
const statsEl = document.getElementById('stationStats');
const nearbyList = document.getElementById('nearbyList');

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[char] ?? char));

function statusColor(status) {
    switch (status) {
        case 'available': return 'green';
        case 'busy': return 'orange';
        case 'offline': return 'red';
        default: return 'gray';
    }
}

function updateStations() {
    const connector = document.getElementById('connectorFilter').value;
    const status = document.getElementById('statusFilter').value;
    const params = new URLSearchParams();
    if (connector) params.append('connector', connector);
    if (status) params.append('status', status);
    const query = params.toString();

    fetch(query ? `api/stations.php?${query}` : 'api/stations.php')
        .then((res) => res.json())
        .then((data) => {
            markers.forEach((marker) => map.removeLayer(marker));
            markers.length = 0;
            if (!Array.isArray(data.stations)) {
                statsEl.innerHTML = '<li>Unable to load station data.</li>';
                return;
            }
            const total = data.stations.length;
            const available = data.stations.filter((s) => s.status === 'available').length;
            const busy = data.stations.filter((s) => s.status === 'busy').length;
            const offline = data.stations.filter((s) => s.status === 'offline').length;
            statsEl.innerHTML = `
                <li><strong>${total}</strong> stations shown</li>
                <li>${available} available · ${busy} busy · ${offline} offline</li>
            `;
            data.stations.forEach((station) => {
                const lat = parseFloat(station.latitude);
                const lng = parseFloat(station.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    return;
                }
                const marker = L.circleMarker([lat, lng], {
                    radius: 10,
                    color: statusColor(station.status),
                    fillColor: statusColor(station.status),
                    fillOpacity: 0.8,
                }).addTo(map);
                marker.bindPopup(`
                    <div class="text-dark">
                        <strong>${escapeHtml(station.name)}</strong><br>
                        Connector: ${escapeHtml(station.connector)}<br>
                        Status: ${escapeHtml(station.status)}<br>
                        Price: ${escapeHtml(station.price_per_kwh)} ETB / kWh
                    </div>
                `);
                marker.station = {
                    ...station,
                    latitude: lat,
                    longitude: lng,
                };
                markers.push(marker);
            });
        })
        .catch(() => {
            statsEl.innerHTML = '<li>Unable to load station data.</li>';
        });
}

function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function listNearby(position) {
    const { latitude, longitude } = position.coords;
    const nearby = markers
        .map((marker) => ({
            station: marker.station,
            distance: haversineDistance(latitude, longitude, marker.station.latitude, marker.station.longitude),
        }))
        .filter((entry) => entry.distance <= 10)
        .sort((a, b) => a.distance - b.distance);

    if (!nearby.length) {
        nearbyList.textContent = 'No chargers found within 10 km of your location yet.';
        return;
    }
    nearbyList.innerHTML = nearby.map((entry) => `
        <div class="mb-2">
            <strong>${escapeHtml(entry.station.name)}</strong><br>
            ${entry.distance.toFixed(2)} km away — ${escapeHtml(entry.station.status)}
        </div>
    `).join('');
}

document.getElementById('connectorFilter').addEventListener('change', updateStations);

document.getElementById('statusFilter').addEventListener('change', updateStations);

document.getElementById('nearbyButton').addEventListener('click', () => {
    nearbyList.textContent = 'Requesting your location…';
    if (!navigator.geolocation) {
        nearbyList.textContent = 'Geolocation is not supported on this device.';
        return;
    }
    navigator.geolocation.getCurrentPosition(listNearby, () => {
        nearbyList.textContent = 'Unable to access your location.';
    });
});

updateStations();

const upgradeButton = document.getElementById('upgradeButton');
if (upgradeButton && !upgradeButton.disabled) {
    upgradeButton.addEventListener('click', () => {
        upgradeButton.disabled = true;
        document.getElementById('paymentMessage').textContent = 'Contacting Chapa…';
        fetch('api/payments/init.php', { method: 'POST' })
            .then((res) => res.json())
            .then((data) => {
                if (data.checkout_url) {
                    document.getElementById('paymentMessage').textContent = data.message || 'Redirecting to payment…';
                    window.location.href = data.checkout_url;
                } else {
                    throw new Error(data.error || 'Unable to initialize payment.');
                }
            })
            .catch((err) => {
                upgradeButton.disabled = false;
                document.getElementById('paymentMessage').textContent = err.message;
            });
    });
}
</script>
</body>
</html>
