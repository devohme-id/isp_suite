<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Technician']); // Admin & Tech only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    // Create OLT
    if ($action === 'create_olt') {
        $name = clean_input($_POST['olt_name']);
        $model = clean_input($_POST['olt_model']);
        $olt_type = clean_input($_POST['olt_type'] ?? 'GPON');
        $ip = clean_input($_POST['ip_address']);
        $location = clean_input($_POST['location']);
        $ports = (int)$_POST['total_ports'];
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);

        // Validate olt_type
        if (!in_array($olt_type, ['GPON', 'EPON'])) {
            $olt_type = 'GPON';
        }

        if (empty($name) || $ports <= 0) {
            set_flash_message('error', 'Nama OLT dan Jumlah Port harus diisi dengan benar.');
            header('Location: ../pages/ftth_network.php?tab=olt');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check unique name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM olts WHERE olt_name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Nama OLT sudah digunakan.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=olt');
                exit();
            }

            // Insert OLT
            $stmt = $pdo->prepare("INSERT INTO olts (olt_name, olt_model, olt_type, ip_address, location, total_ports, latitude, longitude, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $model, $olt_type, $ip, $location, $ports, $lat, $lng, $notes]);
            $olt_id = $pdo->lastInsertId();

            // Create Ports with dynamic type based on OLT type
            $stmt_port = $pdo->prepare("INSERT INTO olt_ports (olt_id, port_number, port_label, port_type, status) VALUES (?, ?, ?, ?, 'inactive')");
            for ($i = 1; $i <= $ports; $i++) {
                $port_label = $olt_type . " PORT " . $i;
                $stmt_port->execute([$olt_id, $i, $port_label, $olt_type]);
            }

            $pdo->commit();
            set_flash_message('success', 'OLT berhasil ditambahkan beserta port default.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menambahkan OLT: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=olt');
        exit();
    }

    // Update OLT
    elseif ($action === 'update_olt') {
        $id = (int)($_POST['id'] ?? 0);
        $name = clean_input($_POST['olt_name']);
        $model = clean_input($_POST['olt_model']);
        $olt_type = clean_input($_POST['olt_type'] ?? 'GPON');
        $ip = clean_input($_POST['ip_address']);
        $location = clean_input($_POST['location']);
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);

        // Validate olt_type
        if (!in_array($olt_type, ['GPON', 'EPON'])) {
            $olt_type = 'GPON';
        }

        if (!$id || empty($name)) {
            set_flash_message('error', 'ID OLT dan Nama OLT tidak boleh kosong.');
            header('Location: ../pages/ftth_network.php?tab=olt');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check unique name excluding self
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM olts WHERE olt_name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Nama OLT sudah digunakan oleh OLT lain.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=olt');
                exit();
            }

            $stmt = $pdo->prepare("UPDATE olts SET olt_name = ?, olt_model = ?, olt_type = ?, ip_address = ?, location = ?, latitude = ?, longitude = ?, notes = ? WHERE id = ?");
            $stmt->execute([$name, $model, $olt_type, $ip, $location, $lat, $lng, $notes, $id]);

            $pdo->commit();
            set_flash_message('success', 'Data OLT berhasil diperbarui.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal memperbarui OLT: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=olt');
        exit();
    }

    // Delete OLT
    elseif ($action === 'delete_olt') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            set_flash_message('error', 'ID OLT tidak valid.');
            header('Location: ../pages/ftth_network.php?tab=olt');
            exit();
        }

        try {
            // Check if any port on this OLT is connected to a backbone cable
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM backbones b 
                JOIN olt_ports p ON b.olt_port_id = p.id 
                WHERE p.olt_id = ?
            ");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Gagal menghapus: OLT masih memiliki port yang terhubung ke kabel backbone.');
                header('Location: ../pages/ftth_network.php?tab=olt');
                exit();
            }

            // Safe to delete (cascade will delete ports)
            $stmt = $pdo->prepare("DELETE FROM olts WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'OLT berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus OLT: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=olt');
        exit();
    }

    // Update Port (e.g. Type, Label, Status)
    elseif ($action === 'update_port') {
        $port_id = (int)($_POST['port_id'] ?? 0);
        $olt_id = (int)($_POST['olt_id'] ?? 0);
        $label = clean_input($_POST['port_label']);
        $type = clean_input($_POST['port_type']);
        $status = clean_input($_POST['status']);
        $notes = clean_input($_POST['notes']);

        if (!$port_id) {
            set_flash_message('error', 'ID Port tidak valid.');
            header('Location: ../pages/olt_detail.php?id=' . $olt_id);
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE olt_ports SET port_label = ?, port_type = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$label, $type, $status, $notes, $port_id]);
            set_flash_message('success', 'Port OLT berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal memperbarui port OLT: ' . $e->getMessage());
        }
        header('Location: ../pages/olt_detail.php?id=' . $olt_id);
        exit();
    }
}
?>
