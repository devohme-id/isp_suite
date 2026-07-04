<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Technician']); // Admin & Tech only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    // Create Distribution Cable
    if ($action === 'create') {
        $code = clean_input($_POST['dist_code']);
        $rk_id = !empty($_POST['rk_id']) ? (int)$_POST['rk_id'] : null;
        $cable_type = clean_input($_POST['cable_type']);
        $tubes = (int)$_POST['total_tubes'];
        $cores = (int)$_POST['cores_per_tube'];
        $coverage = clean_input($_POST['coverage_area']);
        $notes = clean_input($_POST['notes']);

        if (empty($code) || $tubes <= 0 || $cores <= 0) {
            set_flash_message('error', 'Kode Distribusi, Jumlah Tube, dan Core per Tube harus diisi dengan benar.');
            header('Location: ../pages/ftth_network.php?tab=cable'); // Or distribution management tab/page
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check unique code
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM distributions WHERE dist_code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Kode Distribusi sudah digunakan.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=cable');
                exit();
            }

            // Insert distribution
            $stmt = $pdo->prepare("INSERT INTO distributions (dist_code, rk_id, cable_type, total_tubes, cores_per_tube, coverage_area, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $rk_id, $cable_type, $tubes, $cores, $coverage, $notes]);
            $dist_id = $pdo->lastInsertId();

            // Create cores
            $stmt_core = $pdo->prepare("INSERT INTO distribution_cores (distribution_id, tube_number, core_number, status) VALUES (?, ?, ?, 'idle')");
            for ($t = 1; $t <= $tubes; $t++) {
                for ($c = 1; $c <= $cores; $c++) {
                    $stmt_core->execute([$dist_id, $t, $c]);
                }
            }

            $pdo->commit();
            set_flash_message('success', 'Kabel Distribusi berhasil ditambahkan beserta detail core.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menambahkan Distribusi: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=cable'); // We can redirect to a combined cable page or back
        exit();
    }

    // Update Distribution Cable
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $code = clean_input($_POST['dist_code']);
        $rk_id = !empty($_POST['rk_id']) ? (int)$_POST['rk_id'] : null;
        $cable_type = clean_input($_POST['cable_type']);
        $coverage = clean_input($_POST['coverage_area']);
        $notes = clean_input($_POST['notes']);

        if (!$id || empty($code)) {
            set_flash_message('error', 'ID dan Kode Distribusi tidak boleh kosong.');
            header('Location: ../pages/ftth_network.php?tab=cable');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check unique code excluding self
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM distributions WHERE dist_code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Kode Distribusi sudah digunakan oleh kabel lain.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=cable');
                exit();
            }

            $stmt = $pdo->prepare("UPDATE distributions SET dist_code = ?, rk_id = ?, cable_type = ?, coverage_area = ?, notes = ? WHERE id = ?");
            $stmt->execute([$code, $rk_id, $cable_type, $coverage, $notes, $id]);

            $pdo->commit();
            set_flash_message('success', 'Data Kabel Distribusi berhasil diperbarui.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=cable');
        exit();
    }

    // Delete Distribution Cable
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            set_flash_message('error', 'ID Kabel Distribusi tidak valid.');
            header('Location: ../pages/ftth_network.php?tab=cable');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check if any cores are linked to drop_points
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM drop_points dp
                JOIN distribution_cores dc ON dp.dist_core_id = dc.id
                WHERE dc.distribution_id = ?
            ");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Gagal menghapus: Core dari kabel distribusi ini sedang mensuplai Drop Point (DP). Putuskan link DP terlebih dahulu.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=cable');
                exit();
            }

            // Safe to delete (cascade deletes distribution cores)
            $stmt = $pdo->prepare("DELETE FROM distributions WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            set_flash_message('success', 'Kabel Distribusi berhasil dihapus.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menghapus Distribusi: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=cable');
        exit();
    }

    // Update Core Notes / Status
    elseif ($action === 'update_core') {
        $core_id = (int)($_POST['core_id'] ?? 0);
        $status = clean_input($_POST['status']);
        $notes = clean_input($_POST['notes']);

        if (!$core_id) {
            set_flash_message('error', 'ID Core tidak valid.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE distribution_cores SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $core_id]);
            set_flash_message('success', 'Status core distribusi berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal memperbarui core: ' . $e->getMessage());
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
