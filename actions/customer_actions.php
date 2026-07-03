<?php
require_once '../config.php';
require_login();

// Helper to generate Customer Code
function generate_customer_code($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM customers");
    $max_id = $stmt->fetch()['max_id'] ?? 0;
    return 'CST-' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $phone = clean_input($_POST['phone']);
        $address = clean_input($_POST['address']);
        $package_id = (int) $_POST['package_id'];
        $install_date = $_POST['installation_date'];
        $lat = $_POST['latitude'] ?? null;
        $long = $_POST['longitude'] ?? null;
        // New Fields
        $mac = clean_input($_POST['mac_address'] ?? '');
        $ip = clean_input($_POST['ip_address'] ?? '');
        $odp_id = !empty($_POST['odp_id']) ? (int) $_POST['odp_id'] : null;
        $odp_port = !empty($_POST['odp_port']) ? (int) $_POST['odp_port'] : null;
        
        $code = generate_customer_code($pdo);

        // Validation: Check ODP Port Collision
        if ($odp_id && $odp_port) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE odp_id = ? AND odp_port = ?");
            $check->execute([$odp_id, $odp_port]);
            if ($check->fetchColumn() > 0) {
                set_flash_message('error', "Gagal: Port $odp_port pada ODP tersebut sudah terisi.");
                header("Location: ../pages/customers.php");
                exit();
            }
        }

        $stmt = $pdo->prepare("INSERT INTO customers (customer_code, name, email, phone, address, package_id, installation_date, latitude, longitude, mac_address, ip_address, odp_id, odp_port) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$code, $name, $email, $phone, $address, $package_id, $install_date, $lat, $long, $mac, $ip, $odp_id, $odp_port]);
            set_flash_message('success', 'Pelanggan berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menambah pelanggan: ' . $e->getMessage());
        }

    } elseif ($action === 'update') {
        $id = (int) $_POST['id'];
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $phone = clean_input($_POST['phone']);
        $address = clean_input($_POST['address']);
        $package_id = (int) $_POST['package_id'];
        $lat = $_POST['latitude'] ?? null;
        $long = $_POST['longitude'] ?? null;
        $status = $_POST['status'];
        // New Fields
        $mac = clean_input($_POST['mac_address'] ?? '');
        $ip = clean_input($_POST['ip_address'] ?? '');
        $odp_id = !empty($_POST['odp_id']) ? (int) $_POST['odp_id'] : null;
        $odp_port = !empty($_POST['odp_port']) ? (int) $_POST['odp_port'] : null;

        // Validation: Check ODP Port Collision (excluding self)
        if ($odp_id && $odp_port) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE odp_id = ? AND odp_port = ? AND id != ?");
            $check->execute([$odp_id, $odp_port, $id]);
            if ($check->fetchColumn() > 0) {
                set_flash_message('error', "Gagal: Port $odp_port pada ODP tersebut sudah terisi oleh pelanggan lain.");
                header("Location: ../pages/customers.php");
                exit();
            }
        }

        $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, package_id=?, latitude=?, longitude=?, status=?, mac_address=?, ip_address=?, odp_id=?, odp_port=? WHERE id=?");
        try {
            $stmt->execute([$name, $email, $phone, $address, $package_id, $lat, $long, $status, $mac, $ip, $odp_id, $odp_port, $id]);
            set_flash_message('success', 'Data pelanggan berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Update gagal: ' . $e->getMessage());
        }

    } elseif ($action === 'approve') {
        $id = (int) $_POST['id'];
        $install_date = str_replace('T', ' ', $_POST['installation_date']); // Fix datetime-local format
        
        // 1. Update Status & Installation Date
        $stmt = $pdo->prepare("UPDATE customers SET status='active', installation_date=? WHERE id=?");
        try {
            $stmt->execute([$install_date, $id]);
            
            // 2. Fetch Customer Info for WA
            $query = $pdo->prepare("SELECT name, phone FROM customers WHERE id=?");
            $query->execute([$id]);
            $cust = $query->fetch();

            if ($cust) {
                // Determine Greeting
                $hour = date('H');
                $greeting = ($hour < 12) ? 'Selamat pagi' : (($hour < 15) ? 'Selamat siang' : (($hour < 18) ? 'Selamat sore' : 'Selamat malam'));

                // Format DateTime
                $dateObj = new DateTime($install_date);
                $dateFormatted = $dateObj->format('d/m/Y H:i');

                // Clean Phone (Ensure it starts with international code, e.g. 62)
                $phone = preg_replace('/[^0-9]/', '', $cust['phone']);
                if (substr($phone, 0, 1) === '0') {
                    $phone = '62' . substr($phone, 1);
                }

                // Construct Message
                $message = "$greeting Kak {$cust['name']},\n\nPendaftaran Wifi Anda telah kami *APPROVE*.\nJadwal Instalasi: *$dateFormatted*\n\nTeknisi kami akan segera menghubungi Anda. Terima kasih telah berlangganan!";
                
                $wa_url = "https://wa.me/$phone?text=" . urlencode($message);

                // Set Flash Message with specific type to trigger JS open
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pelanggan berhasil diapprove. Membuka WhatsApp...'];
                $_SESSION['whatsapp_link'] = $wa_url;
            } else {
                set_flash_message('success', 'Pelanggan berhasil diapprove.');
            }

        } catch (PDOException $e) {
            set_flash_message('error', 'Approval gagal: ' . $e->getMessage());
        }

    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        // Check invoices
        $check = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            set_flash_message('error', 'Gagal hapus: Pelanggan memiliki history tagihan.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Pelanggan berhasil dihapus.');
        }
    }

    header("Location: ../pages/customers.php");
    exit();
}
?>
