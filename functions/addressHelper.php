<?php
// File: functions/addressHelper.php
require_once __DIR__ . '/../classes/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$pdo = Database::getPDO();

try {
    if ($action === 'regions') {
        $stmt = $pdo->query("SELECT id, name FROM regions ORDER BY name ASC");
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($action === 'provinces') {
        $stmt = $pdo->prepare("SELECT id, name FROM provinces WHERE region_id = ? ORDER BY name ASC");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($action === 'cities') {
        $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE province_id = ? ORDER BY name ASC");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($action === 'barangays') {
        $stmt = $pdo->prepare("SELECT id, name FROM barangays WHERE city_id = ? ORDER BY name ASC");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll());
    }
} catch (Exception $e) {
    echo json_encode([]);
}
?>