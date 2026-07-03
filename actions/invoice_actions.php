<?php
require_once '../config.php';
require_once '../includes/MikrotikApi.php';
require_login();
require_role(['Administrator', 'Finance']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_payment') {
        $invoice_id = (int) $_POST['invoice_id'];
        $method = clean_input($_POST['payment_method']);
        
        // Fetch Correct Amount from Invoice
        $stmt = $pdo->prepare("SELECT amount FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            set_flash_message('error', 'Tagihan tidak ditemukan.');
            header("Location: ../pages/invoices.php");
            exit();
        }
        $amount_paid = $invoice['amount'];

        // Handle File Upload (Optional)
        $fileName = null;
        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['proof_file']['tmp_name'];
            $fileOriginalName = basename($_FILES['proof_file']['name']);
            
            // Validate File Size (Max 2MB)
            if ($_FILES['proof_file']['size'] > 2 * 1024 * 1024) {
                 set_flash_message('error', 'Ukuran file maks 2MB.');
                 header("Location: ../pages/invoices.php");
                 exit();
            }

            // Validate MIME Type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($fileTmp);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!in_array($mime, $allowed_mimes)) {
                set_flash_message('error', 'Hanya file gambar (JPG, PNG) yang diperbolehkan.');
                header("Location: ../pages/invoices.php");
                 exit();
            }

            $newFileName = time() . '_' . $fileOriginalName;
            $dest = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmp, $dest)) {
                $fileName = $newFileName;
            } else {
                set_flash_message('error', 'Gagal memindahkan file upload.');
                header("Location: ../pages/invoices.php");
                exit();
            }
        } elseif (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] !== UPLOAD_ERR_NO_FILE) {
             // Error other than "No file uploaded"
             set_flash_message('error', 'Terjadi kesalahan saat upload file.');
             header("Location: ../pages/invoices.php");
             exit();
        }

        // Proceed to Insert Payment
        try {
            $pdo->beginTransaction();
            
            $auto_verify = isset($_POST['auto_verify']) && $_POST['auto_verify'] == '1';
            
            // Determine Statuses
            if ($auto_verify) {
                // If auto-verified
                $payment_verified_by = $_SESSION['user_id'];
                $invoice_status = 'paid';
                $flash_msg = 'Pembayaran berhasil disimpan dan diverifikasi (Lunas).';
            } else {
                // Standard flow
                $payment_verified_by = NULL;
                $invoice_status = 'pending';
                $flash_msg = 'Konfirmasi pembayaran berhasil dikirim. Menunggu verifikasi.';
            }

            $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, payment_method, amount_paid, proof_file, verified_by, status) VALUES (?, ?, ?, ?, ?, 'verified')");
            $stmt->execute([$invoice_id, $method, $amount_paid, $fileName, $payment_verified_by]);

            // Update Invoice Status
            $upd = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
            $upd->execute([$invoice_status, $invoice_id]);

            $pdo->commit();
            set_flash_message('success', $flash_msg);

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal simpan: ' . $e->getMessage());
        }

    } elseif ($action === 'verify_payment') {
        $payment_id = (int) $_POST['payment_id'];
        $invoice_id = (int) $_POST['invoice_id'];
        $decision = $_POST['decision']; // approve / reject

        try {
            $pdo->beginTransaction();

            if ($decision === 'approve') {
                // Update Payment
                $stmt = $pdo->prepare("UPDATE payments SET verified_by = ?, status = 'verified' WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $payment_id]);

                // Update Invoice
                $upd = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
                $upd->execute([$invoice_id]);

                // MikroTik: Enable binding if no more overdue invoices
                $custStmt = $pdo->prepare("SELECT customer_id FROM invoices WHERE id = ?");
                $custStmt->execute([$invoice_id]);
                $customerId = $custStmt->fetchColumn();
                if ($customerId) {
                    $api = getMikrotikApiFromSettings($pdo);
                    if ($api) {
                        syncCustomerBinding($pdo, $api, $customerId);
                        $api->disconnect();
                    }
                }
                
                set_flash_message('success', 'Pembayaran diterima. Invoice Lunas.');
            } else {
                // Reject
                $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected', verified_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $payment_id]);

                // Revert Invoice to Unpaid
                $upd = $pdo->prepare("UPDATE invoices SET status = 'unpaid' WHERE id = ?");
                $upd->execute([$invoice_id]);

                set_flash_message('warning', 'Pembayaran ditolak.');

                // MikroTik: Sync binding (may need to disable if overdue)
                $custStmt = $pdo->prepare("SELECT customer_id FROM invoices WHERE id = ?");
                $custStmt->execute([$invoice_id]);
                $customerId = $custStmt->fetchColumn();
                if ($customerId) {
                    $api = getMikrotikApiFromSettings($pdo);
                    if ($api) {
                        syncCustomerBinding($pdo, $api, $customerId);
                        $api->disconnect();
                    }
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal verifikasi: ' . $e->getMessage());
        }

    } elseif ($action === 'rollback_payment') {
        $payment_id = (int) $_POST['payment_id'];
        $invoice_id = (int) $_POST['invoice_id'];

        try {
            $pdo->beginTransaction();

            // Revert Payment Verification (Set verified_by to NULL)
            // Note: We keep status as 'verified' because 'pending' is not in enum, 
            // and based on logic, 'verified' + null verifier = pending check.
            $stmt = $pdo->prepare("UPDATE payments SET verified_by = NULL WHERE id = ?");
            $stmt->execute([$payment_id]);

            // Revert Invoice to Pending
            $upd = $pdo->prepare("UPDATE invoices SET status = 'pending' WHERE id = ?");
            $upd->execute([$invoice_id]);

            $pdo->commit();

            // MikroTik: Sync binding (may need to disable if customer has overdue invoices)
            $custStmt = $pdo->prepare("SELECT customer_id FROM invoices WHERE id = ?");
            $custStmt->execute([$invoice_id]);
            $customerId = $custStmt->fetchColumn();
            if ($customerId) {
                $api = getMikrotikApiFromSettings($pdo);
                if ($api) {
                    syncCustomerBinding($pdo, $api, $customerId);
                    $api->disconnect();
                }
            }

            set_flash_message('warning', 'Konfirmasi dibatalkan. Status invoice kembali ke Menunggu Verifikasi.');

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal rollback: ' . $e->getMessage());
        }

    } elseif ($action === 'batch_confirm') {
        $ids = $_POST['invoice_ids'] ?? [];
        if (empty($ids)) {
            set_flash_message('error', 'Tidak ada tagihan yang dipilih.');
        } else {
            $success_count = 0;
            $customerIds = []; // Track customers to sync with MikroTik
            try {
                $pdo->beginTransaction();
                
                foreach ($ids as $inv_id) {
                    $inv_id = (int)$inv_id;
                    // Get Invoice Info
                    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
                    $stmt->execute([$inv_id]);
                    $inv = $stmt->fetch();

                    if (!$inv || $inv['status'] == 'paid') continue;

                    if ($inv['status'] == 'unpaid' || $inv['status'] == 'overdue') {
                        // Create Payment
                        $ins = $pdo->prepare("INSERT INTO payments (invoice_id, payment_method, amount_paid, payment_date, verified_by, status, notes) VALUES (?, 'cash', ?, NOW(), ?, 'verified', 'Batch Confirmation')");
                        $ins->execute([$inv_id, $inv['amount'], $_SESSION['user_id']]);
                        
                        // Update Invoice
                        $upd = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
                        $upd->execute([$inv_id]);
                        
                        // Queue customer for MikroTik sync
                        $customerIds[] = $inv['customer_id'];
                        $success_count++;

                    } elseif ($inv['status'] == 'pending') {
                        // Approve existing payment
                        $upd_pay = $pdo->prepare("UPDATE payments SET verified_by = ?, status = 'verified' WHERE invoice_id = ? AND status = 'verified' IS NULL"); 
                        // Note: Actually default status for upload might be null? No, database default is 'verified' in schema but logic earlier uses 'pending' implicit?
                        // Let's check upload logic: It creates payment with verified_by=NULL. status default is 'verified' in DB but we might rely on verified_by being null.
                        // Wait, previous verify logic: "UPDATE payments SET verified_by = $user_id, status = 'verified'".
                        // So let's do the same.
                        $upd_pay = $pdo->prepare("UPDATE payments SET verified_by = ?, status = 'verified' WHERE invoice_id = ? ORDER BY id DESC LIMIT 1");
                        $upd_pay->execute([$_SESSION['user_id'], $inv_id]);
                        
                        $upd = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
                        $upd->execute([$inv_id]);
                        $success_count++;
                    }
                }

                $pdo->commit();

                // MikroTik: Sync all affected customers
                if (!empty($customerIds)) {
                    $api = getMikrotikApiFromSettings($pdo);
                    if ($api) {
                        foreach (array_unique($customerIds) as $custId) {
                            syncCustomerBinding($pdo, $api, $custId);
                        }
                        $api->disconnect();
                    }
                }

                set_flash_message('success', "Berhasil mengonfirmasi $success_count tagihan.");

            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('error', 'Gagal batch confirm: ' . $e->getMessage());
            }
        }
    }

    // Build Redirect URL with State
    $redirect_url = "invoices.php";
    $params = [];
    
    if (isset($_POST['redirect_page']) && $_POST['redirect_page'] > 1) {
        $params['page'] = $_POST['redirect_page'];
    }
    if (!empty($_POST['redirect_status'])) {
        $params['status'] = $_POST['redirect_status'];
    }
    if (!empty($_POST['redirect_month'])) {
        $params['month'] = $_POST['redirect_month'];
    }
    if (!empty($_POST['redirect_year'])) {
        $params['year'] = $_POST['redirect_year'];
    }
    if (!empty($_POST['redirect_search'])) {
        $params['search'] = $_POST['redirect_search'];
    }

    if (!empty($params)) {
        $redirect_url .= "?" . http_build_query($params);
    }

    header("Location: ../pages/" . $redirect_url);
    exit();
}
?>
