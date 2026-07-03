<?php
// auth.php - Authentication Logic

require_once 'config.php';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    
    // Validate CSRF
    verify_csrf_token($_POST['csrf_token']);

    // --- Rate Limiting Logic ---
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $limit_time = 15; // minutes
    $max_attempts = 5;

    // 1. Clean up old attempts
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL ? MINUTE)");
    $stmt->execute([$limit_time]);

    // 2. Check current attempts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip_address]);
    $attempts = $stmt->fetchColumn();

    if ($attempts >= $max_attempts) {
        set_flash_message('error', 'Terlalu banyak percobaan login gagal. Silakan coba lagi dalam 15 menit.');
        header("Location: login.php");
        exit();
    }
    // --- End Rate Limiting ---

    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
        header("Location: login.php");
        exit();
    }

    // Check User
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login Success
        session_regenerate_id(true); // Security: Prevent Session Fixation
        
        // Clear fails
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip_address]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role'] = $user['role_name']; // Administrator, Finance, Technician

        // Redirect based on role (for now just dashboard)
        header("Location: " . BASE_URL . "/pages/dashboard.php");
        exit();
    } else {
        // Login Failed -> Record Attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
        $stmt->execute([$ip_address]);

        set_flash_message('error', 'Email atau password salah.');
        header("Location: login.php");
        exit();
    }
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
