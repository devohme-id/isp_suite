<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Technician']); // Admin & Tech only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    
    // Create
    if ($action === 'create') {
        $name = clean_input($_POST['odp_name']);
        $zone = clean_input($_POST['zone_area']);
        $ports = (int)$_POST['total_ports'];
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);

        try {
            $stmt = $pdo->prepare("INSERT INTO odp_points (odp_name, zone_area, total_ports, latitude, longitude, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $zone, $ports, $lat, $lng, $notes]);
            set_flash_message('success', 'New ODP created successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Failed to create ODP: ' . $e->getMessage());
        }
    }

    // Update
    elseif ($action === 'update' && $id) {
        $name = clean_input($_POST['odp_name']);
        $zone = clean_input($_POST['zone_area']);
        $ports = (int)$_POST['total_ports'];
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);

        try {
            $stmt = $pdo->prepare("UPDATE odp_points SET odp_name=?, zone_area=?, total_ports=?, latitude=?, longitude=?, notes=? WHERE id=?");
            $stmt->execute([$name, $zone, $ports, $lat, $lng, $notes, $id]);
            set_flash_message('success', 'ODP updated successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Failed to update ODP: ' . $e->getMessage());
        }
    }

    // Delete
    elseif ($action === 'delete' && $id) {
        // Check usage first
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE odp_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Cannot delete ODP while assigned to customers. Unassign existing customers first.');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM odp_points WHERE id = ?");
                $stmt->execute([$id]);
                set_flash_message('success', 'ODP deleted successfully.');
            } catch (PDOException $e) {
                set_flash_message('error', 'Failed to delete ODP: ' . $e->getMessage());
            }
        }
    }

    header('Location: ../pages/odp_management.php');
    exit();
}
?>
