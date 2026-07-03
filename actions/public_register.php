<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    verify_csrf_token($_POST['csrf_token'] ?? '');

    // Validate inputs
    // Note: Public forms are vulnerable to spam, basic validation here.
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    $package_id = (int) $_POST['package_id'];
    
    // Status 'pending' is key here
    $status = 'pending';
    
    // Generate Customer Code (Duplicate logic for now to avoid refactoring risk)
    // Format: CST-XXX (but we should check if we can make it distinct for pending? No, keep standard)
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM customers");
    $max_id = $stmt->fetch()['max_id'] ?? 0;
    $customer_code = 'CST-' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);

    // Insert
    // Installation date is NULL initially for pending
    // Mac/IP are NULL
    try {
        $stmt = $pdo->prepare("INSERT INTO customers (customer_code, name, email, phone, address, package_id, status, installation_date) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
        $stmt->execute([$customer_code, $name, $email, $phone, $address, $package_id, $status]);
        
        $_SESSION['public_flash'] = "Pendaftaran berhasil! Tim kami akan menghubungi Anda via WhatsApp untuk penjadwalan.";
    } catch (PDOException $e) {
        // Handle specific errors like duplicate email/code if unique constraint exists
        // simplified error handling for public
        $_SESSION['public_flash'] = "Terjadi kesalahan: Gagal mengirim data. Silakan coba lagi atau hubungi admin."; 
        // Log actual error internally if needed: error_log($e->getMessage());
    }

    header("Location: ../pages/landing.php#register");
    exit();
} else {
    header("Location: ../pages/landing.php");
    exit();
}
?>
