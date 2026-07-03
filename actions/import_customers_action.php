<?php
require_once '../config.php';
require_once '../includes/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

require_login();

// Increase memory and time limit for large files
ini_set('memory_limit', '128M');
set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    verify_csrf_token($_POST['csrf_token']);

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('error', 'Gagal upload file. Silakan coba lagi.');
        header("Location: ../pages/customers.php");
        exit;
    }

    $file = $_FILES['file']['tmp_name'];
    
    // Validate extension
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'xlsx') {
        set_flash_message('error', 'Format file harus .xlsx');
        header("Location: ../pages/customers.php");
        exit;
    }

    if ($xlsx = SimpleXLSX::parse($file)) {
        $rows = $xlsx->rows();
        $count_success = 0;
        $count_fail = 0;
        
        // Helper to get Valid Package IDs (Simple cache)
        $packages_stmt = $pdo->query("SELECT id FROM internet_packages");
        $valid_package_ids = [];
        while($row = $packages_stmt->fetch()) {
            $valid_package_ids[] = $row['id'];
        }

        // Get max customer ID for code generation
        $stmt_max = $pdo->query("SELECT MAX(id) as max_id FROM customers");
        $max_id = $stmt_max->fetch()['max_id'] ?? 0;
        $next_id = $max_id + 1;

        // Expected Headers (Flexible comparison)
        $expected_headers = ['Code', 'Name', 'Email', 'Phone', 'Address', 'Lat', 'Long', 'PackageID', 'InstallDate', 'DueDate', 'Status'];
        
        // Detailed Error Log
        $errors = [];

        foreach ($rows as $k => $r) {
            // Validate Header (Row 0)
            if ($k === 0) {
                // Check first few valid headers to ensure format is mostly correct
                // We check if "Name" and "Phone" (critical columns) are roughly in the expected positions or present
                // Simple strict check for this project:
                $header_row = array_map('strtolower', array_map('trim', $r));
                
                // Key columns we look for to verify it's the right template
                // Index 1: Name, Index 3: Phone
                $valid_format = true;
                if (!isset($header_row[1]) || !str_contains($header_row[1], 'name') && !str_contains($header_row[1], 'nama')) $valid_format = false;
                if (!isset($header_row[3]) || !str_contains($header_row[3], 'phone') && !str_contains($header_row[3], 'hp')) $valid_format = false;

                if (!$valid_format) {
                     set_flash_message('error', '<b>Format File Salah!</b><br>Header tidak sesuai template. Pastikan Anda menggunakan template terbaru.<br>Kolom wajib: Name, Phone/HP.');
                     header("Location: ../pages/customers.php");
                     exit;
                }
                continue; 
            }

            $row_num = $k + 1;
            
            // Extract Values
            $input_code = clean_input($r[0] ?? '');
            $name = clean_input($r[1] ?? '');
            $email = clean_input($r[2] ?? '');
            $phone = clean_input($r[3] ?? '');
            $address = clean_input($r[4] ?? '');
            $lat = $r[5] ?? null;
            $long = $r[6] ?? null;
            $package_id = (int)($r[7] ?? 0);
            $install_date = $r[8] ?? date('Y-m-d');
            $due_date = (int)($r[9] ?? 10);
            $status = strtolower(trim($r[10] ?? 'active'));

            // 1. Critical Field Validation
            if (empty($name)) {
                $count_fail++;
                $errors[] = "Baris $row_num: Nama wajib diisi.";
                continue;
            }
            if (empty($phone)) {
                $count_fail++;
                $errors[] = "Baris $row_num: No HP wajib diisi.";
                continue;
            }

            // 2. Package Validation
            if (!in_array($package_id, $valid_package_ids)) {
                 $count_fail++;
                 $errors[] = "Baris $row_num: ID Paket '$package_id' tidak valid.";
                 continue; 
            }
            
            // Format Date
            try {
                // Handle Excel Date serial number if applicable, or string
                if (is_numeric($install_date)) {
                   $install_date = date('Y-m-d', $xlsx->unixstamp($install_date));
                } else {
                   $d = new DateTime($install_date);
                   $install_date = $d->format('Y-m-d');
                }
            } catch (Exception $e) {
                $install_date = date('Y-m-d');
            }
            
            // Validate due date
            if ($due_date < 1 || $due_date > 28) $due_date = 10;

            // Generate or Use Code
            if (!empty($input_code)) {
                $customer_code = $input_code;
            } else {
                $customer_code = 'CST-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
                $next_id++; 
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO customers (customer_code, name, email, phone, address, latitude, longitude, package_id, installation_date, due_date_day, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$customer_code, $name, $email, $phone, $address, $lat, $long, $package_id, $install_date, $due_date, $status]);
                $count_success++;
            } catch (PDOException $e) {
                $count_fail++;
                // Handle duplicate
                if ($e->getCode() == 23000) {
                     $errors[] = "Baris $row_num: Kode '$customer_code' atau data duplikat.";
                } else {
                     $errors[] = "Baris $row_num: Error database.";
                }
            }
        }
        
        // Final Feedback Construction
        if ($count_fail > 0) {
            $msg_type = $count_success > 0 ? 'warning' : 'error';
            $error_summary = implode("<br>", array_slice($errors, 0, 5)); // Show max 5 errors
            if (count($errors) > 5) $error_summary .= "<br>...dan " . (count($errors) - 5) . " error lainnya.";
            
            set_flash_message($msg_type, "Import Selesai: $count_success Berhasil, $count_fail Gagal.<br><span class='text-xs mt-2 block'>$error_summary</span>");
        } else {
            set_flash_message('success', "Import Berhasil! $count_success data pelanggan ditambahkan.");
        }

    } else {
        set_flash_message('error', SimpleXLSX::parseError());
    }
    
    header("Location: ../pages/customers.php");
    exit;
} else {
    header("Location: ../pages/customers.php");
    exit;
}
