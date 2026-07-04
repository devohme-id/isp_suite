<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Technician']); // Admin & Tech only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    // Create Backbone
    if ($action === 'create') {
        $code = clean_input($_POST['backbone_code']);
        $olt_port_id = !empty($_POST['olt_port_id']) ? (int)$_POST['olt_port_id'] : null;
        $cable_type = clean_input($_POST['cable_type']);
        $tubes = (int)$_POST['total_tubes'];
        $cores = (int)$_POST['cores_per_tube'];
        $route = clean_input($_POST['route_description']);
        $notes = clean_input($_POST['notes']);

        if (empty($code) || $tubes <= 0 || $cores <= 0) {
            set_flash_message('error', 'Kode Backbone, Jumlah Tube, dan Core per Tube harus diisi dengan benar.');
            header('Location: ../pages/ftth_network.php?tab=cable');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check unique code
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM backbones WHERE backbone_code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Kode Backbone sudah digunakan.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=cable');
                exit();
            }

            // Check if OLT Port is already assigned
            if ($olt_port_id) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM backbones WHERE olt_port_id = ?");
                $stmt->execute([$olt_port_id]);
                if ($stmt->fetchColumn() > 0) {
                    set_flash_message('error', 'Port OLT tersebut sudah terhubung ke backbone lain.');
                    $pdo->rollBack();
                    header('Location: ../pages/ftth_network.php?tab=cable');
                    exit();
                }
            }

            // Insert Backbone
            $stmt = $pdo->prepare("INSERT INTO backbones (backbone_code, olt_port_id, cable_type, total_tubes, cores_per_tube, route_description, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $olt_port_id, $cable_type, $tubes, $cores, $route, $notes]);
            $backbone_id = $pdo->lastInsertId();

            // Auto-create cores
            $stmt_core = $pdo->prepare("INSERT INTO backbone_cores (backbone_id, tube_number, core_number, status) VALUES (?, ?, ?, 'idle')");
            for ($t = 1; $t <= $tubes; $t++) {
                for ($c = 1; $c <= $cores; $c++) {
                    $stmt_core->execute([$backbone_id, $t, $c]);
                }
            }

            // Mark OLT Port as Active
            if ($olt_port_id) {
                $stmt_olt = $pdo->prepare("UPDATE olt_ports SET status = 'active' WHERE id = ?");
                $stmt_olt->execute([$olt_port_id]);
            }

            $pdo->commit();
            set_flash_message('success', 'Kabel Backbone berhasil ditambahkan beserta detail core.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menambahkan Backbone: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=cable');
        exit();
    }

    // Update Backbone
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $code = clean_input($_POST['backbone_code']);
        $olt_port_id = !empty($_POST['olt_port_id']) ? (int)$_POST['olt_port_id'] : null;
        $cable_type = clean_input($_POST['cable_type']);
        $route = clean_input($_POST['route_description']);
        $notes = clean_input($_POST['notes']);

        if (!$id || empty($code)) {
            set_flash_message('error', 'ID dan Kode Backbone tidak boleh kosong.');
            header('Location: ../pages/ftth_network.php?tab=cable');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check unique code excluding self
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM backbones WHERE backbone_code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Kode Backbone sudah digunakan oleh kabel lain.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=cable');
                exit();
            }

            // Get current OLT port
            $stmt_cur = $pdo->prepare("SELECT olt_port_id FROM backbones WHERE id = ?");
            $stmt_cur->execute([$id]);
            $old_port_id = $stmt_cur->fetchColumn();

            // If OLT port is changed
            if ($old_port_id != $olt_port_id) {
                // If new port is assigned, make sure it is not used elsewhere
                if ($olt_port_id) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM backbones WHERE olt_port_id = ? AND id != ?");
                    $stmt->execute([$olt_port_id, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        set_flash_message('error', 'Port OLT baru sudah terhubung ke backbone lain.');
                        $pdo->rollBack();
                        header('Location: ../pages/ftth_network.php?tab=cable');
                        exit();
                    }

                    // Set new port status to active
                    $stmt_olt = $pdo->prepare("UPDATE olt_ports SET status = 'active' WHERE id = ?");
                    $stmt_olt->execute([$olt_port_id]);
                }

                // Restore old port status to inactive if it was set
                if ($old_port_id) {
                    $stmt_olt = $pdo->prepare("UPDATE olt_ports SET status = 'inactive' WHERE id = ?");
                    $stmt_olt->execute([$old_port_id]);
                }
            }

            $stmt = $pdo->prepare("UPDATE backbones SET backbone_code = ?, olt_port_id = ?, cable_type = ?, route_description = ?, notes = ? WHERE id = ?");
            $stmt->execute([$code, $olt_port_id, $cable_type, $route, $notes, $id]);

            $pdo->commit();
            set_flash_message('success', 'Data Backbone berhasil diperbarui.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal memperbarui Backbone: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=cable');
        exit();
    }

    // Delete Backbone
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            set_flash_message('error', 'ID Backbone tidak valid.');
            header('Location: ../pages/ftth_network.php?tab=cable');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Check if any cores are in rk_connections
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM rk_connections rc
                JOIN backbone_cores bc ON rc.backbone_core_id = bc.id
                WHERE bc.backbone_id = ?
            ");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Gagal menghapus: Core dari backbone ini masih tersambung di RK.');
                $pdo->rollBack();
                header('Location: ../pages/ftth_network.php?tab=cable');
                exit();
            }

            // Restore OLT port status to inactive
            $stmt_port = $pdo->prepare("SELECT olt_port_id FROM backbones WHERE id = ?");
            $stmt_port->execute([$id]);
            $port_id = $stmt_port->fetchColumn();
            if ($port_id) {
                $stmt_olt = $pdo->prepare("UPDATE olt_ports SET status = 'inactive' WHERE id = ?");
                $stmt_olt->execute([$port_id]);
            }

            // Safe to delete (cascade deletes cores)
            $stmt = $pdo->prepare("DELETE FROM backbones WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            set_flash_message('success', 'Kabel Backbone berhasil dihapus.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menghapus Backbone: ' . $e->getMessage());
        }
        header('Location: ../pages/ftth_network.php?tab=cable');
        exit();
    }

    // Update Core Notes / Status / OLT Port mapping
    elseif ($action === 'update_core') {
        $core_id = (int)($_POST['core_id'] ?? 0);
        $status = clean_input($_POST['status']);
        $notes = clean_input($_POST['notes']);
        $olt_port_id = !empty($_POST['olt_port_id']) ? (int)$_POST['olt_port_id'] : null;

        if (!$core_id) {
            set_flash_message('error', 'ID Core tidak valid.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Validate that the port is not already assigned to another core
            if ($olt_port_id) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM backbone_cores WHERE olt_port_id = ? AND id != ?");
                $stmt->execute([$olt_port_id, $core_id]);
                if ($stmt->fetchColumn() > 0) {
                    set_flash_message('error', 'Port OLT tersebut sudah terhubung ke core backbone lain.');
                    $pdo->rollBack();
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit();
                }
            }

            // Get current olt_port_id of the core
            $stmt_cur = $pdo->prepare("SELECT olt_port_id FROM backbone_cores WHERE id = ?");
            $stmt_cur->execute([$core_id]);
            $old_port_id = $stmt_cur->fetchColumn();

            // If OLT port connection has changed
            if ($old_port_id != $olt_port_id) {
                if ($olt_port_id) {
                    $stmt_olt = $pdo->prepare("UPDATE olt_ports SET status = 'active' WHERE id = ?");
                    $stmt_olt->execute([$olt_port_id]);
                }
                if ($old_port_id) {
                    $stmt_olt = $pdo->prepare("UPDATE olt_ports SET status = 'inactive' WHERE id = ?");
                    $stmt_olt->execute([$old_port_id]);
                }
            }

            $stmt = $pdo->prepare("UPDATE backbone_cores SET status = ?, notes = ?, olt_port_id = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $olt_port_id, $core_id]);

            $pdo->commit();
            set_flash_message('success', 'Detail core backbone berhasil diperbarui.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal memperbarui core: ' . $e->getMessage());
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
