<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Technician']); // Admin & Tech only

// Helper to auto-generate DP code from distribution core
function generate_dp_code($pdo, $dist_core_id) {
    if (!$dist_core_id) return null;
    
    $stmt = $pdo->prepare("
        SELECT d.dist_code, dc.tube_number, dc.core_number 
        FROM distribution_cores dc 
        JOIN distributions d ON dc.distribution_id = d.id 
        WHERE dc.id = ?
    ");
    $stmt->execute([$dist_core_id]);
    $info = $stmt->fetch();
    
    if (!$info) return null;
    
    // Parse dist_code (e.g. DIST-A01)
    // Format: DIST-[BlockChar][BackboneIndex]
    $block = 'A';
    $bb = '01';
    
    if (preg_match('/DIST-([A-Z])([0-9]+)/i', $info['dist_code'], $matches)) {
        $block = strtoupper($matches[1]);
        $bb = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    } else {
        // Fallback search inside code for any letter followed by numbers
        if (preg_match('/([A-Z])([0-9]+)/i', $info['dist_code'], $matches)) {
            $block = strtoupper($matches[1]);
            $bb = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        }
    }
    
    $tube = str_pad($info['tube_number'], 2, '0', STR_PAD_LEFT);
    $core = str_pad($info['core_number'], 2, '0', STR_PAD_LEFT);
    
    return "DP-" . $block . $bb . $tube . $core;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    
    // Create Drop Point (DP)
    if ($action === 'create') {
        $name = clean_input($_POST['dp_name']);
        $zone = clean_input($_POST['zone_area']);
        $ports = (int)$_POST['total_ports'];
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);
        $dist_core_id = !empty($_POST['dist_core_id']) ? (int)$_POST['dist_core_id'] : null;

        try {
            $pdo->beginTransaction();

            // Check if distribution core is already assigned to a DP
            if ($dist_core_id) {
                $check_core = $pdo->prepare("SELECT COUNT(*) FROM drop_points WHERE dist_core_id = ?");
                $check_core->execute([$dist_core_id]);
                if ($check_core->fetchColumn() > 0) {
                    set_flash_message('error', 'Gagal: Core kabel distribusi yang dipilih sudah terhubung ke DP lain.');
                    $pdo->rollBack();
                    header('Location: ../pages/ftth_network.php?tab=dp');
                    exit();
                }
            }

            // Generate DP code
            $dp_code = generate_dp_code($pdo, $dist_core_id);
            if (!$dp_code) {
                // Generik fallback
                $stmt_max = $pdo->query("SELECT MAX(id) FROM drop_points");
                $max_id = (int)$stmt_max->fetchColumn();
                $dp_code = 'DP-GEN-' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);
            }

            // Check unique dp_code
            $check_code = $pdo->prepare("SELECT COUNT(*) FROM drop_points WHERE dp_code = ?");
            $check_code->execute([$dp_code]);
            if ($check_code->fetchColumn() > 0) {
                // If collision occurs (e.g. code exists), append a unique index
                $dp_code .= '-1';
            }

            $stmt = $pdo->prepare("INSERT INTO drop_points (dp_code, dp_name, dist_core_id, zone_area, total_ports, latitude, longitude, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dp_code, $name, $dist_core_id, $zone, $ports, $lat, $lng, $notes]);

            // Update distribution core status to active
            if ($dist_core_id) {
                $stmt_core = $pdo->prepare("UPDATE distribution_cores SET status = 'active' WHERE id = ?");
                $stmt_core->execute([$dist_core_id]);
            }

            $pdo->commit();
            set_flash_message('success', "Drop Point (DP) baru berhasil ditambahkan dengan kode: $dp_code");
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal membuat Drop Point: ' . $e->getMessage());
        }
    }

    // Update Drop Point (DP)
    elseif ($action === 'update' && $id) {
        $name = clean_input($_POST['dp_name']);
        $zone = clean_input($_POST['zone_area']);
        $ports = (int)$_POST['total_ports'];
        $lat = clean_input($_POST['latitude']);
        $lng = clean_input($_POST['longitude']);
        $notes = clean_input($_POST['notes']);
        $dist_core_id = !empty($_POST['dist_core_id']) ? (int)$_POST['dist_core_id'] : null;

        try {
            $pdo->beginTransaction();

            // Get current distribution core of this DP
            $stmt_cur = $pdo->prepare("SELECT dist_core_id FROM drop_points WHERE id = ?");
            $stmt_cur->execute([$id]);
            $old_core_id = $stmt_cur->fetchColumn();

            // If core changed
            if ($old_core_id != $dist_core_id) {
                // If new core is chosen, ensure it is not used elsewhere
                if ($dist_core_id) {
                    $check_core = $pdo->prepare("SELECT COUNT(*) FROM drop_points WHERE dist_core_id = ? AND id != ?");
                    $check_core->execute([$dist_core_id, $id]);
                    if ($check_core->fetchColumn() > 0) {
                        set_flash_message('error', 'Gagal: Core kabel distribusi yang dipilih sudah terhubung ke DP lain.');
                        $pdo->rollBack();
                        header('Location: ../pages/ftth_network.php?tab=dp');
                        exit();
                    }

                    // Update new core to active
                    $stmt_core = $pdo->prepare("UPDATE distribution_cores SET status = 'active' WHERE id = ?");
                    $stmt_core->execute([$dist_core_id]);
                }

                // Restore old core to idle
                if ($old_core_id) {
                    $stmt_core = $pdo->prepare("UPDATE distribution_cores SET status = 'idle' WHERE id = ?");
                    $stmt_core->execute([$old_core_id]);
                }
            }

            // Regenerate DP code if core changed (or if it doesn't have a valid custom one)
            $dp_code = generate_dp_code($pdo, $dist_core_id);
            if ($dp_code) {
                $stmt = $pdo->prepare("UPDATE drop_points SET dp_code = ?, dp_name = ?, dist_core_id = ?, zone_area = ?, total_ports = ?, latitude = ?, longitude = ?, notes = ? WHERE id = ?");
                $stmt->execute([$dp_code, $name, $dist_core_id, $zone, $ports, $lat, $lng, $notes, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE drop_points SET dp_name = ?, dist_core_id = ?, zone_area = ?, total_ports = ?, latitude = ?, longitude = ?, notes = ? WHERE id = ?");
                $stmt->execute([$name, $dist_core_id, $zone, $ports, $lat, $lng, $notes, $id]);
            }

            $pdo->commit();
            set_flash_message('success', 'Drop Point (DP) berhasil diperbarui.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal memperbarui Drop Point: ' . $e->getMessage());
        }
    }

    // Delete Drop Point (DP)
    elseif ($action === 'delete' && $id) {
        // Check customer usage
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE dp_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Gagal menghapus: Drop Point ini sedang digunakan oleh pelanggan. Hubungkan pelanggan ke Drop Point lain terlebih dahulu.');
        } else {
            try {
                $pdo->beginTransaction();

                // Get current core to release it
                $stmt_core = $pdo->prepare("SELECT dist_core_id FROM drop_points WHERE id = ?");
                $stmt_core->execute([$id]);
                $core_id = $stmt_core->fetchColumn();

                if ($core_id) {
                    $stmt_release = $pdo->prepare("UPDATE distribution_cores SET status = 'idle' WHERE id = ?");
                    $stmt_release->execute([$core_id]);
                }

                $stmt = $pdo->prepare("DELETE FROM drop_points WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                set_flash_message('success', 'Drop Point (DP) berhasil dihapus.');
            } catch (PDOException $e) {
                $pdo->rollBack();
                set_flash_message('error', 'Gagal menghapus Drop Point: ' . $e->getMessage());
            }
        }
    }

    header('Location: ../pages/ftth_network.php?tab=dp');
    exit();
}
?>
