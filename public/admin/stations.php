<?php // path: public/admin/stations.php
require_once __DIR__ . '/../../app/auth.php';

require_admin();
$pdo = get_pdo();
$actionMessage = null;

if (request_method('POST')) {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $connector = trim($_POST['connector'] ?? '');
    $status = trim($_POST['status'] ?? 'available');
    $price = (float)($_POST['price_per_kwh'] ?? 0);
    $latitude = (float)($_POST['latitude'] ?? 0);
    $longitude = (float)($_POST['longitude'] ?? 0);

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO stations (name, connector, status, price_per_kwh, latitude, longitude, created_at) VALUES (:name, :connector, :status, :price, :lat, :lng, :created_at)');
        $stmt->execute([
            'name' => $name,
            'connector' => $connector,
            'status' => $status,
            'price' => $price,
            'lat' => $latitude,
            'lng' => $longitude,
            'created_at' => current_datetime(),
        ]);
        $actionMessage = 'Station created successfully.';
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE stations SET name = :name, connector = :connector, status = :status, price_per_kwh = :price, latitude = :lat, longitude = :lng WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'connector' => $connector,
            'status' => $status,
            'price' => $price,
            'lat' => $latitude,
            'lng' => $longitude,
            'id' => $id,
        ]);
        $actionMessage = 'Station updated successfully.';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM stations WHERE id = :id')->execute(['id' => $id]);
        $actionMessage = 'Station deleted successfully.';
    }
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$currentStation = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM stations WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $currentStation = $stmt->fetch();
}

$stations = $pdo->query('SELECT * FROM stations ORDER BY name ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — Stations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="bg-dark text-light min-vh-100 d-flex flex-column">
<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard.php">SOCKET Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="../dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="stations.php">Stations</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-grow-1">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card bg-black border border-secondary">
                    <div class="card-body">
                        <h1 class="h5 mb-3"><?= $currentStation ? 'Edit station' : 'Add new station'; ?></h1>
                        <?php if ($actionMessage): ?>
                            <div class="alert alert-success py-2"><?= sanitize($actionMessage); ?></div>
                        <?php endif; ?>
                        <form method="post" class="vstack gap-3">
                            <input type="hidden" name="action" value="<?= $currentStation ? 'update' : 'create'; ?>">
                            <?php if ($currentStation): ?>
                                <input type="hidden" name="id" value="<?= (int)$currentStation['id']; ?>">
                            <?php endif; ?>
                            <div>
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control bg-dark text-light border-secondary" required value="<?= sanitize($currentStation['name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="form-label">Connector</label>
                                <select name="connector" class="form-select bg-dark text-light border-secondary">
                                    <?php
                                    $connectors = ['CCS', 'Type2', 'CHAdeMO'];
                                    $selectedConnector = $currentStation['connector'] ?? '';
                                    foreach ($connectors as $connectorOption):
                                        $selected = $connectorOption === $selectedConnector ? 'selected' : '';
                                        echo '<option value="' . $connectorOption . '" ' . $selected . '>' . $connectorOption . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select bg-dark text-light border-secondary">
                                    <?php
                                    $statuses = ['available', 'busy', 'offline'];
                                    $selectedStatus = $currentStation['status'] ?? 'available';
                                    foreach ($statuses as $statusOption):
                                        $selected = $statusOption === $selectedStatus ? 'selected' : '';
                                        echo '<option value="' . $statusOption . '" ' . $selected . '>' . ucfirst($statusOption) . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Price per kWh (ETB)</label>
                                <input type="number" step="0.01" name="price_per_kwh" class="form-control bg-dark text-light border-secondary" value="<?= sanitize($currentStation['price_per_kwh'] ?? '0'); ?>">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" name="latitude" class="form-control bg-dark text-light border-secondary" value="<?= sanitize($currentStation['latitude'] ?? ''); ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" name="longitude" class="form-control bg-dark text-light border-secondary" value="<?= sanitize($currentStation['longitude'] ?? ''); ?>">
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit"><?= $currentStation ? 'Update station' : 'Add station'; ?></button>
                            <?php if ($currentStation): ?>
                                <a href="stations.php" class="btn btn-outline-light">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card bg-black border border-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">All stations</h2>
                            <a href="stations.php" class="btn btn-outline-light btn-sm">Refresh</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Connector</th>
                                        <th>Status</th>
                                        <th>Price (ETB/kWh)</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($stations as $station): ?>
                                    <tr>
                                        <td><?= sanitize($station['name']); ?></td>
                                        <td><?= sanitize($station['connector']); ?></td>
                                        <td><span class="badge bg-<?= $station['status'] === 'available' ? 'success' : ($station['status'] === 'busy' ? 'warning text-dark' : 'danger'); ?>"><?= sanitize(ucfirst($station['status'])); ?></span></td>
                                        <td><?= sanitize(number_format((float)$station['price_per_kwh'], 2)); ?></td>
                                        <td><?= sanitize($station['latitude']); ?></td>
                                        <td><?= sanitize($station['longitude']); ?></td>
                                        <td class="text-end">
                                            <a href="stations.php?id=<?= (int)$station['id']; ?>" class="btn btn-outline-light btn-sm">Edit</a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this station?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$station['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$stations): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-secondary">No stations yet.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
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
</body>
</html>
