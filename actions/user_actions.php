<?php
// user_actions.php - Handle User CRUD Operations
require_once '../config.php';
require_login();

// Enforce Admin Access
if ($_SESSION['role'] !== 'Administrator') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF Check
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? '';

    // --- ADD USER ---
    if ($action === 'add') {
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $password = $_POST['password'];
        $role_id = (int)$_POST['role_id'];

        // Backend Validation
        if (empty($name) || empty($email) || empty($password) || empty($role_id)) {
            set_flash_message('error', 'Semua field wajib diisi.');
            header("Location: ../pages/users.php");
            exit();
        }

        // Check Email Uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            set_flash_message('error', 'Email sudah terdaftar. Gunakan email lain.');
            header("Location: ../pages/users.php");
            exit();
        }

        // Hash Password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $role_id]);
            set_flash_message('success', 'User berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menambahkan user: ' . $e->getMessage());
        }
        
        header("Location: ../pages/users.php");
        exit();
    }

    // --- EDIT USER ---
    if ($action === 'edit') {
        $id = (int)$_POST['user_id'];
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $role_id = (int)$_POST['role_id'];
        $password = $_POST['password']; // Optional

        if (empty($name) || empty($email) || empty($role_id)) {
             set_flash_message('error', 'Nama, Email, dan Role wajib diisi.');
             header("Location: ../pages/users.php");
             exit();
        }

        // Check Email Uniqueness (exclude self)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            set_flash_message('error', 'Email sudah digunakan user lain.');
            header("Location: ../pages/users.php");
            exit();
        }

        try {
            if (!empty($password)) {
                // Update with password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_id = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role_id, $hashed_password, $id]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_id = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role_id, $id]);
            }
             set_flash_message('success', 'Data user berhasil diperbarui.');
        } catch (PDOException $e) {
             set_flash_message('error', 'Gagal update user: ' . $e->getMessage());
        }

        header("Location: ../pages/users.php");
        exit();
    }

    // --- DELETE USER ---
    if ($action === 'delete') {
        $id = (int)$_POST['user_id'];

        // Prevent Self-Delete
        if ($id === $_SESSION['user_id']) {
            set_flash_message('error', 'Anda tidak dapat menghapus akun sendiri.');
            header("Location: ../pages/users.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'User berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus user: ' . $e->getMessage());
        }

        header("Location: ../pages/users.php");
        exit();
    }
}
