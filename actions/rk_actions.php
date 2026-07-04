<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Technician']); // Admin & Tech only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    // Create RK
    if ($action === 'create_rk') {
        $name = clean_input($_POST['rk_name']);
        $location = clean_input($_POST['location_description']);
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);

        if (empty($name)) {
            set_flash_message('error', 'Nama RK tidak boleh kosong.');
            header('Location: ../pages/ftth_network.php?tab=rk');
            exit();
        }

        try {
            // Check unique name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rk_points WHERE rk_name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Nama RK sudah digunakan.');
                header('Location: ../pages/ftth_network.php?tab=rk');
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO rk_points (rk_name, location_description, latitude, longitude, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $location, $lat, $lng, $notes]);
            set_flash_message('success', 'Rumah Kabel (RK) berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menambahkan RK: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=rk');
        exit();
    }

    // Update RK
    elseif ($action === 'update_rk') {
        $id = (int)($_POST['id'] ?? 0);
        $name = clean_input($_POST['rk_name']);
        $location = clean_input($_POST['location_description']);
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);

        if (!$id || empty($name)) {
            set_flash_message('error', 'ID dan Nama RK tidak boleh kosong.');
            header('Location: ../pages/ftth_network.php?tab=rk');
            exit();
        }

        try {
            // Check unique name excluding self
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rk_points WHERE rk_name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Nama RK sudah digunakan oleh RK lain.');
                header('Location: ../pages/ftth_network.php?tab=rk');
                exit();
            }

            $stmt = $pdo->prepare("UPDATE rk_points SET rk_name = ?, location_description = ?, latitude = ?, longitude = ?, notes = ? WHERE id = ?");
            $stmt->execute([$name, $location, $lat, $lng, $notes, $id]);
            set_flash_message('success', 'Data RK berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal memperbarui RK: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=rk');
        exit();
    }

    // Delete RK
    elseif ($action === 'delete_rk') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            set_flash_message('error', 'ID RK tidak valid.');
            header('Location: ../pages/ftth_network.php?tab=rk');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check if any backbone connections exist
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rk_connections WHERE rk_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Gagal menghapus: RK masih memiliki koneksi backbone tersambung. Putuskan koneksi terlebih dahulu.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=rk');
                exit();
            }

            // Check if any distributions are mapped to this RK
            $stmt_dist = $pdo->prepare("SELECT COUNT(*) FROM distributions WHERE rk_id = ?");
            $stmt_dist->execute([$id]);
            if ($stmt_dist->fetchColumn() > 0) {
                set_flash_message('error', 'Gagal menghapus: RK masih digunakan oleh kabel distribusi.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=rk');
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM rk_points WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();
            set_flash_message('success', 'RK berhasil dihapus.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menghapus RK: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=rk');
        exit();
    }

    // Add Backbone Connection to RK
    elseif ($action === 'create_connection') {
        $rk_id = (int)($_POST['rk_id'] ?? 0);
        $backbone_core_id = (int)($_POST['backbone_core_id'] ?? 0);
        $notes = clean_input($_POST['notes']);

        if (!$rk_id || !$backbone_core_id) {
            set_flash_message('error', 'RK dan Core Backbone harus dipilih.');
            header('Location: ../pages/ftth_network.php?tab=rk');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check if already connected
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rk_connections WHERE backbone_core_id = ?");
            $stmt->execute([$backbone_core_id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Core Backbone tersebut sudah terhubung ke RK lain.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=rk');
                exit();
            }

            // Insert connection
            $stmt = $pdo->prepare("INSERT INTO rk_connections (rk_id, backbone_core_id, notes) VALUES (?, ?, ?)");
            $stmt->execute([$rk_id, $backbone_core_id, $notes]);

            // Update core status to active
            $stmt_core = $pdo->prepare("UPDATE backbone_cores SET status = 'active' WHERE id = ?");
            $stmt_core->execute([$backbone_core_id]);

            $pdo->commit();
            set_flash_message('success', 'Koneksi backbone ke RK berhasil ditambahkan.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menyambungkan: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=rk');
        exit();
    }

    // Delete Connection
    elseif ($action === 'delete_connection') {
        $connection_id = (int)($_POST['id'] ?? 0);

        if (!$connection_id) {
            set_flash_message('error', 'ID Koneksi tidak valid.');
            header('Location: ../pages/ftth_network.php?tab=rk');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Get backbone core id
            $stmt = $pdo->prepare("SELECT backbone_core_id FROM rk_connections WHERE id = ?");
            $stmt->execute([$connection_id]);
            $core_id = $stmt->fetchColumn();

            // Delete connection
            $stmt_del = $pdo->prepare("DELETE FROM rk_connections WHERE id = ?");
            $stmt_del->execute([$connection_id]);

            // Restore core status to idle
            if ($core_id) {
                $stmt_core = $pdo->prepare("UPDATE backbone_cores SET status = 'idle' WHERE id = ?");
                $stmt_core->execute([$core_id]);
            }

            $pdo->commit();
            set_flash_message('success', 'Koneksi backbone berhasil diputus.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal memutuskan koneksi: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=rk');
        exit();
    }
}
?>
