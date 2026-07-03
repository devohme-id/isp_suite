<?php
require_once '../config.php';
require_login();
require_role(['Administrator']); // Only admin can manage packages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = clean_input($_POST['package_name']);
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $speed = (int) $_POST['speed_mbps'];
        $desc = clean_input($_POST['description']);

        $stmt = $pdo->prepare("INSERT INTO internet_packages (package_name, price, speed_mbps, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $speed, $desc]);
        set_flash_message('success', 'Paket berhasil ditambahkan.');

    } elseif ($action === 'update') {
        $id = (int) $_POST['id'];
        $name = clean_input($_POST['package_name']);
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $speed = (int) $_POST['speed_mbps'];
        $desc = clean_input($_POST['description']);
        $active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE internet_packages SET package_name=?, price=?, speed_mbps=?, description=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $price, $speed, $desc, $active, $id]);
        set_flash_message('success', 'Paket berhasil diperbarui.');

    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        // Check usage
        $check = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE package_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            set_flash_message('error', 'Gagal hapus: Paket sedang digunakan oleh pelanggan.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM internet_packages WHERE id=?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Paket berhasil dihapus.');
        }
    }

    header("Location: ../pages/packages.php");
    exit();
}
?>
