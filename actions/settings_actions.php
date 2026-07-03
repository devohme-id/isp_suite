<?php
require_once '../config.php';
require_login();
require_once '../auth.php'; // Reuse auth logic if needed, but mostly self-contained

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $action = $_POST['action'];

    if ($action === 'general') {
        // Only Admin can change General Settings
        if ($_SESSION['role'] !== 'Administrator') {
            set_flash_message('error', 'Akses ditolak.');
            header("Location: ../pages/settings.php");
            exit;
        }

        $app_name = clean_input($_POST['app_name']);
        $company_name = clean_input($_POST['company_name']);
        
        // Update DB
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'app_name'");
        $stmt->execute([$app_name]);
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'company_name'");
        $stmt->execute([$company_name]);

        // Handle File Upload (Icon)
        if (isset($_FILES['app_icon']) && $_FILES['app_icon']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/png', 'image/jpeg', 'image/x-icon', 'image/svg+xml'];
            if (!in_array($_FILES['app_icon']['type'], $allowed_types)) {
                set_flash_message('error', 'Format file tidak valid (PNG, JPG, ICO, SVG).');
                header("Location: ../pages/settings.php");
                exit;
            }
            
            // Limit size 500KB
            if ($_FILES['app_icon']['size'] > 500 * 1024) {
                 set_flash_message('error', 'Ukuran file terlalu besar (Max 500KB).');
                 header("Location: ../pages/settings.php");
                 exit;
            }

            $ext = pathinfo($_FILES['app_icon']['name'], PATHINFO_EXTENSION);
            $filename = 'app_icon_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['app_icon']['tmp_name'], UPLOAD_DIR . $filename)) {
                 $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'app_icon'");
                 $stmt->execute([$filename]);
            }
        }

        set_flash_message('success', 'Pengaturan umum berhasil disimpan.');

    } elseif ($action === 'profile') {
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);

        $_SESSION['name'] = $name; // Update Session
        $_SESSION['email'] = $email;

        set_flash_message('success', 'Profil berhasil diperbarui.');

    } elseif ($action === 'security') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $user_id = $_SESSION['user_id'];

        // Get User
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            set_flash_message('error', 'Password saat ini salah.');
            header("Location: ../pages/settings.php");
            exit;
        }

        if ($new_password !== $confirm_password) {
            set_flash_message('error', 'Konfirmasi password baru tidak cocok.');
            header("Location: ../pages/settings.php");
            exit;
        }

        if (strlen($new_password) < 6) {
             set_flash_message('error', 'Password minimal 6 karakter.');
             header("Location: ../pages/settings.php");
             exit;
        }

        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);

        set_flash_message('success', 'Password berhasil diubah.');
    }

    header("Location: ../pages/settings.php");
    exit;
}
?>
