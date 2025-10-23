<?php // path: public/api/stations.php
require_once __DIR__ . '/../../app/auth.php';

$pdo = get_pdo();
$connector = $_GET['connector'] ?? '';
$status = $_GET['status'] ?? '';

$sql = 'SELECT id, name, connector, status, price_per_kwh, latitude, longitude FROM stations WHERE 1=1';
$params = [];
if ($connector !== '') {
    $sql .= ' AND connector = :connector';
    $params['connector'] = $connector;
}
if ($status !== '') {
    $sql .= ' AND status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stations = $stmt->fetchAll();

json_response(['stations' => $stations]);
