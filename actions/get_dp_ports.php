<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$dp_id = isset($_GET['dp_id']) ? (int)$_GET['dp_id'] : 0;

if (!$dp_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid DP ID']);
    exit;
}

try {
    // 1. Get DP Total Ports
    $stmt = $pdo->prepare("SELECT total_ports FROM drop_points WHERE id = ?");
    $stmt->execute([$dp_id]);
    $dp = $stmt->fetch();

    if (!$dp) {
        echo json_encode(['success' => false, 'message' => 'Drop Point not found']);
        exit;
    }

    // 2. Get Occupied Ports
    $stmt_occupied = $pdo->prepare("SELECT dp_port, id as customer_id, name FROM customers WHERE dp_id = ? AND dp_port IS NOT NULL");
    $stmt_occupied->execute([$dp_id]);
    $occupied_data = $stmt_occupied->fetchAll();

    $occupied_ports = [];
    foreach ($occupied_data as $row) {
        $occupied_ports[$row['dp_port']] = [
            'customer_id' => $row['customer_id'],
            'name' => $row['name']
        ];
    }

    echo json_encode([
        'success' => true,
        'total_ports' => (int)$dp['total_ports'],
        'occupied_ports' => $occupied_ports
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
