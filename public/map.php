<?php // path: public/map.php
require_once __DIR__ . '/../app/auth.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Map — SOCKET</title>
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
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/stations.php">Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-grow-1">
    <div class="container py-4">
        <div class="card bg-black border border-secondary">
            <div class="card-body">
                <h1 class="h4 mb-3">Explore charging stations</h1>
                <p class="text-secondary">Live data sourced by SOCKET. Tap a marker to view connector types and pricing.</p>
                <div id="map" style="height: 520px;" class="rounded"></div>
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
const map = L.map('map').setView([8.9806, 38.7578], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[char] ?? char));

fetch('api/stations.php')
    .then((res) => res.json())
    .then((data) => {
        if (!Array.isArray(data.stations)) {
            return;
        }
        data.stations.forEach((station) => {
            const lat = parseFloat(station.latitude);
            const lng = parseFloat(station.longitude);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }
            const marker = L.circleMarker([lat, lng], {
                radius: 10,
                color: station.status === 'available' ? 'green' : station.status === 'busy' ? 'orange' : 'red',
                fillColor: station.status === 'available' ? 'green' : station.status === 'busy' ? 'orange' : 'red',
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
        });
    });
</script>
</body>
</html>
