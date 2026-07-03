<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$odp_id = isset($_GET['odp_id']) ? (int)$_GET['odp_id'] : 0;

if (!$odp_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ODP ID']);
    exit;
}

try {
    // 1. Get ODP Total Ports
    $stmt = $pdo->prepare("SELECT total_ports FROM odp_points WHERE id = ?");
    $stmt->execute([$odp_id]);
    $odp = $stmt->fetch();

    if (!$odp) {
        echo json_encode(['success' => false, 'message' => 'ODP not found']);
        exit;
    }

    // 2. Get Occupied Ports
    $stmt_occupied = $pdo->prepare("SELECT odp_port, id as customer_id, name FROM customers WHERE odp_id = ? AND odp_port IS NOT NULL");
    $stmt_occupied->execute([$odp_id]);
    $occupied_data = $stmt_occupied->fetchAll();

    $occupied_ports = [];
    foreach ($occupied_data as $row) {
        $occupied_ports[$row['odp_port']] = [
            'customer_id' => $row['customer_id'],
            'name' => $row['name']
        ];
    }

    echo json_encode([
        'success' => true,
        'total_ports' => (int)$odp['total_ports'],
        'occupied_ports' => $occupied_ports
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
