<?php // path: public/index.php
require_once __DIR__ . '/../app/auth.php';

$user = current_user();
$config = app_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sanitize($config['app_name']); ?> — Ethiopian EV Charging Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-dark text-light d-flex min-vh-100 flex-column">
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
<main class="flex-grow-1 d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-3">Locate Ethiopian EV Charging Stations with Confidence</h1>
                <p class="lead text-secondary">SOCKET delivers live station availability, connector types, and transparent pricing for drivers navigating Addis Ababa and beyond.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
                    <a class="btn btn-primary btn-lg" href="<?= $user ? 'dashboard.php' : 'register.php'; ?>"><?= $user ? 'Open Dashboard' : 'Start Free Trial'; ?></a>
                    <a class="btn btn-outline-light btn-lg" href="map.php">View Map</a>
                </div>
                <div class="mt-5 row g-4 text-start text-secondary">
                    <div class="col-md-4">
                        <h2 class="h5 text-white">Real-time Map</h2>
                        <p>Filter by connector, availability, and price while staying focused on Addis Ababa and the Ethiopian highways.</p>
                    </div>
                    <div class="col-md-4">
                        <h2 class="h5 text-white">5-Day Free Trial</h2>
                        <p>Explore SOCKET premium insights, including congestion alerts and station contact details, before subscribing.</p>
                    </div>
                    <div class="col-md-4">
                        <h2 class="h5 text-white">Secure Chapa Payments</h2>
                        <p>Upgrade to a 30-day subscription using the Chapa sandbox for fast, mobile-first transactions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<footer class="bg-black text-secondary py-3 text-center small">
    <p class="mb-0">&copy; <?= date('Y'); ?> SOCKET — Ethiopian EV Charging Finder.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch(console.error);
}
</script>
</body>
</html>
