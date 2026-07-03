<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Finance']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $expense_date = clean_input($_POST['expense_date']);
        $category = clean_input($_POST['category']);
        $description = clean_input($_POST['description']);
        // Parse amount: Remove non-numeric characters (handles Rp, dots, spaces, NBSP)
        $amount = (float) preg_replace('/[^0-9]/', '', $_POST['amount']);
        $expense_id = isset($_POST['expense_id']) ? (int)$_POST['expense_id'] : null;

        // Validation
        if (empty($expense_date) || empty($category) || empty($amount)) {
            set_flash_message('error', 'Semua field wajib diisi.');
            header("Location: ../pages/expenses.php");
            exit();
        }

        // Handle File Upload
        $fileName = null;
        if ($action === 'update') {
            // Get existing filename if updating
            $stmt = $pdo->prepare("SELECT proof_file FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            $existing = $stmt->fetch();
            $fileName = $existing['proof_file'] ?? null;
        }

        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['proof_file']['tmp_name'];
            $fileOriginalName = $_FILES['proof_file']['name'];
            
            // Validate File Size (Max 2MB)
            if ($_FILES['proof_file']['size'] > 2 * 1024 * 1024) {
                 set_flash_message('error', 'Ukuran file maks 2MB.');
                 header("Location: ../pages/expenses.php");
                 exit();
            }

            // Validate MIME Type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($fileTmp);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            
            if (!in_array($mime, $allowed_mimes)) {
                set_flash_message('error', 'Hanya file gambar (JPG, PNG) atau PDF yang diperbolehkan.');
                header("Location: ../pages/expenses.php");
                exit();
            }

            $newFileName = 'exp_' . time() . '_' . $fileOriginalName;
            $dest = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmp, $dest)) {
                // Delete old file if updating and exists
                if ($fileName && file_exists(UPLOAD_DIR . $fileName)) {
                    unlink(UPLOAD_DIR . $fileName);
                }
                $fileName = $newFileName;
            } else {
                set_flash_message('error', 'Gagal memindahkan file upload.');
                header("Location: ../pages/expenses.php");
                exit();
            }
        }

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, category, description, amount, proof_file) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$expense_date, $category, $description, $amount, $fileName]);
                set_flash_message('success', 'Pengeluaran berhasil ditambahkan.');
            } else {
                $stmt = $pdo->prepare("UPDATE expenses SET expense_date = ?, category = ?, description = ?, amount = ?, proof_file = ? WHERE id = ?");
                $stmt->execute([$expense_date, $category, $description, $amount, $fileName, $expense_id]);
                set_flash_message('success', 'Pengeluaran berhasil diperbarui.');
            }

        } catch (Exception $e) {
            set_flash_message('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }

    } elseif ($action === 'delete') {
        $expense_id = (int) $_POST['expense_id'];
        
        try {
            // Get file name to delete
            $stmt = $pdo->prepare("SELECT proof_file FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            $expense = $stmt->fetch();

            if ($expense && $expense['proof_file'] && file_exists(UPLOAD_DIR . $expense['proof_file'])) {
                unlink(UPLOAD_DIR . $expense['proof_file']);
            }

            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            set_flash_message('success', 'Pengeluaran berhasil dihapus.');

        } catch (Exception $e) {
             set_flash_message('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    header("Location: ../pages/expenses.php");
    exit();
}
?>
