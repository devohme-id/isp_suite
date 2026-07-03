<?php
/**
 * MikroTik Actions Handler
 * 
 * Handles admin actions for MikroTik integration:
 * - Test connection
 * - Update settings
 * - Manual binding operations
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/MikrotikApi.php';

require_login();
require_role(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        $action = $_POST['action'] ?? '';

        if ($action === 'update_settings') {

        // Update MikroTik settings
        $settings = [
            'mikrotik_enabled' => isset($_POST['mikrotik_enabled']) ? '1' : '0',
            'mikrotik_host' => clean_input($_POST['mikrotik_host'] ?? ''),
            'mikrotik_port' => isset($_POST['mikrotik_port']) && $_POST['mikrotik_port'] !== '' ? (int)$_POST['mikrotik_port'] : null,
            'mikrotik_user' => clean_input($_POST['mikrotik_user'] ?? ''),
        ];

        // Only update password if provided (encrypt before saving)
        $newPassword = $_POST['mikrotik_password'] ?? '';
        if (!empty($newPassword)) {
            $settings['mikrotik_password'] = encrypt_data($newPassword);
        }

        try {
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            set_flash_message('success', 'Pengaturan MikroTik berhasil disimpan.');
        } catch (Exception $e) {
            set_flash_message('error', 'Gagal menyimpan: ' . $e->getMessage());
        }

        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();

    } elseif ($action === 'test_connection') {
        // Test MikroTik connection
        $host = clean_input($_POST['mikrotik_host'] ?? '');
        $port = isset($_POST['mikrotik_port']) && $_POST['mikrotik_port'] !== '' ? (int)$_POST['mikrotik_port'] : null;
        $user = clean_input($_POST['mikrotik_user'] ?? '');
        $pass = $_POST['mikrotik_password'] ?? '';

        // If password is empty, try to get from DB (decrypt after reading)
        if (empty($pass)) {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'mikrotik_password'");
            $stmt->execute();
            $encryptedPass = $stmt->fetchColumn() ?: '';
            $pass = decrypt_data($encryptedPass);
        }

        if (empty($host) || $port === null || empty($user)) {
            set_flash_message('error', 'Koneksi gagal: Host, Port, dan Username wajib diisi.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        $api = new MikrotikApi($host, $port);
        
        if (!$api->connect()) {
            set_flash_message('error', 'Koneksi gagal: ' . $api->getLastError());
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        if (!$api->login($user, $pass)) {
            $api->disconnect();
            set_flash_message('error', 'Login gagal: ' . $api->getLastError());
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        // Try to get bindings to verify full access
        $bindings = $api->getIpBindings();
        $bindingCount = count($bindings);
        $api->disconnect();

        set_flash_message('success', "Koneksi berhasil! Ditemukan $bindingCount IP binding.");
        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();

    } elseif ($action === 'sync_customer') {
        // Sync single customer binding
        $customerId = (int)($_POST['customer_id'] ?? 0);
        
        if ($customerId <= 0) {
            set_flash_message('error', 'Customer ID tidak valid.');
            header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
            exit();
        }

        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
            exit();
        }

        $result = syncCustomerBinding($pdo, $api, $customerId);
        $api->disconnect();

        if ($result['success']) {
            set_flash_message('success', $result['message']);
        } elseif (isset($result['skipped']) && $result['skipped']) {
            set_flash_message('warning', $result['message']);
        } else {
            set_flash_message('error', $result['message']);
        }

        header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
        exit();

    } elseif ($action === 'enable_binding') {
        // Manually enable a customer's binding
        $customerId = (int)($_POST['customer_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT mac_address, ip_address, name FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        $mac = strtoupper(trim($customer['mac_address'] ?? ''));
        $ip = trim($customer['ip_address'] ?? '');
        $hasValidMac = !empty($mac) && strlen($mac) >= 12;
        $hasValidIp = !empty($ip);

        if (!$customer || (!$hasValidMac && !$hasValidIp)) {
            set_flash_message('error', 'Customer tidak ditemukan atau tidak memiliki MAC/IP address.');
            header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
            exit();
        }

        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
            exit();
        }

        // Find binding by MAC or IP
        $binding = $api->findBindingByMacOrIp($mac, $ip);
        
        if ($binding && isset($binding['.id'])) {
            if ($api->setBindingDisabled($binding['.id'], false)) {
                set_flash_message('success', "Binding untuk {$customer['name']} berhasil diaktifkan.");
            } else {
                set_flash_message('error', 'Gagal mengaktifkan binding: ' . $api->getLastError());
            }
        } else {
            set_flash_message('error', "Binding tidak ditemukan untuk MAC: $mac atau IP: $ip");
        }

        $api->disconnect();
        header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
        exit();

    } elseif ($action === 'disable_binding') {
        // Manually disable a customer's binding
        $customerId = (int)($_POST['customer_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT mac_address, ip_address, name FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        $mac = strtoupper(trim($customer['mac_address'] ?? ''));
        $ip = trim($customer['ip_address'] ?? '');
        $hasValidMac = !empty($mac) && strlen($mac) >= 12;
        $hasValidIp = !empty($ip);

        if (!$customer || (!$hasValidMac && !$hasValidIp)) {
            set_flash_message('error', 'Customer tidak ditemukan atau tidak memiliki MAC/IP address.');
            header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
            exit();
        }

        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
            exit();
        }

        // Find binding by MAC or IP
        $binding = $api->findBindingByMacOrIp($mac, $ip);
        
        if ($binding && isset($binding['.id'])) {
            if ($api->setBindingDisabled($binding['.id'], true)) {
                set_flash_message('success', "Binding untuk {$customer['name']} berhasil dinonaktifkan.");
            } else {
                set_flash_message('error', 'Gagal menonaktifkan binding: ' . $api->getLastError());
            }
        } else {
            set_flash_message('error', "Binding tidak ditemukan untuk MAC: $mac atau IP: $ip");
        }

        $api->disconnect();
        header("Location: " . ($_POST['redirect'] ?? '../pages/customers.php'));
        exit();

    } elseif ($action === 'sync_overdue_bindings') {
        // Sync all overdue customers - disable their bindings in MikroTik
        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        try {
            // Get all customers with overdue invoices
            $stmt = $pdo->query("
                SELECT DISTINCT c.id, c.name, c.mac_address, c.ip_address, c.status
                FROM customers c
                INNER JOIN invoices i ON i.customer_id = c.id
                WHERE i.status = 'overdue' OR (i.status = 'unpaid' AND i.due_date < CURDATE())
            ");
            $overdueCustomers = $stmt->fetchAll();

            $disabled = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($overdueCustomers as $customer) {
                $result = syncCustomerBinding($pdo, $api, $customer['id']);
                if ($result['success']) {
                    if (isset($result['action']) && $result['action'] === 'disabled') {
                        $disabled++;
                    }
                } elseif (isset($result['skipped']) && $result['skipped']) {
                    $skipped++;
                } else {
                    $failed++;
                }
            }

            $api->disconnect();
            set_flash_message('success', "Sync selesai. Disabled: $disabled, Skipped: $skipped, Failed: $failed");
        } catch (Exception $e) {
            $api->disconnect();
            set_flash_message('error', 'Error: ' . $e->getMessage());
        }

        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();

    } elseif ($action === 'sync_bindings_to_customers') {
        // BIDIRECTIONAL SYNC: MikroTik IP-binding as master source
        // 1. Update existing customers with matching MAC
        // 2. Create new customers from unmatched bindings (package_id = null)
        // 3. Terminate customers not found in bindings
        
        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        try {
            // Get all bindings from MikroTik
            $bindings = $api->getIpBindings();
            $api->disconnect();

            if (empty($bindings)) {
                set_flash_message('warning', 'Tidak ada IP binding ditemukan di MikroTik.');
                header("Location: ../pages/settings.php?tab=mikrotik");
                exit();
            }

            $pdo->beginTransaction();

            $updated = 0;
            $created = 0;
            $terminated = 0;
            $skipped = 0;

            // Skip prefixes (same as full_migration)
            $skipPrefixes = ['LAP', 'Loco', 'CCTV', 'NVR', 'LIVE'];

            // Track IPs processed from MikroTik (IP is now the lookup key)
            $processedIPs = [];

            foreach ($bindings as $binding) {
                $mac = isset($binding['mac-address']) ? strtoupper(trim($binding['mac-address'])) : '';
                $address = isset($binding['address']) ? trim($binding['address']) : '';
                $toAddress = isset($binding['to-address']) ? trim($binding['to-address']) : '';
                $comment = isset($binding['comment']) ? trim($binding['comment']) : '';
                $isDisabled = isset($binding['disabled']) && $binding['disabled'] === 'true';

                // SKIP: address AND to-address both empty (no IP = can't process)
                if (empty($address) && empty($toAddress)) {
                    $skipped++;
                    continue;
                }

                // Use address or to-address as IP
                $ip = !empty($address) ? $address : $toAddress;

                // Track this IP as processed
                $processedIPs[] = $ip;

                // SKIP: comment starts with skip prefixes
                $shouldSkip = false;
                foreach ($skipPrefixes as $prefix) {
                    if (stripos($comment, $prefix) === 0) {
                        $shouldSkip = true;
                        break;
                    }
                }
                if ($shouldSkip) {
                    $skipped++;
                    continue;
                }

                // Parse comment for name-address
                $customerName = '';
                $customerAddress = 'Dari MikroTik';
                
                if (!empty($comment) && strpos($comment, '-') !== false) {
                    $parts = explode('-', $comment, 2);
                    $customerName = trim($parts[0]);
                    if (isset($parts[1]) && !empty(trim($parts[1]))) {
                        $customerAddress = trim($parts[1]);
                    }
                } elseif (!empty($comment)) {
                    $customerName = $comment;
                } else {
                    $customerName = 'Customer-' . substr(str_replace(':', '', $mac ?: 'NONAME'), -6);
                }

                // STEP 1: Find customer by IP address (primary lookup)
                $stmt = $pdo->prepare("
                    SELECT id, mac_address, ip_address, status 
                    FROM customers 
                    WHERE ip_address = ?
                ");
                $stmt->execute([$ip]);
                $customer = $stmt->fetch();

                // STEP 2: If not found by IP, try by name (fallback for IP mismatch cases)
                if (!$customer && !empty($customerName)) {
                    $stmt = $pdo->prepare("
                        SELECT id, mac_address, ip_address, status 
                        FROM customers 
                        WHERE LOWER(name) = LOWER(?)
                    ");
                    $stmt->execute([$customerName]);
                    $customer = $stmt->fetch();
                }

                // Check if MAC is empty/invalid
                $macIsEmpty = empty(trim($mac)) 
                    || strlen(trim($mac)) < 12 
                    || $mac === '\\\\' 
                    || preg_match('/^[0:]+$/', str_replace('-', ':', $mac))
                    || $mac === '-';

                // RULE 2: MAC empty + IP exists + disabled -> TERMINATED
                if ($macIsEmpty && $isDisabled) {
                    if ($customer && $customer['status'] !== 'terminated') {
                        $upd = $pdo->prepare("UPDATE customers SET status = 'terminated', mac_address = NULL WHERE id = ?");
                        $upd->execute([$customer['id']]);
                        $terminated++;
                    }
                    continue;
                }

                // RULE 3: MAC exists + IP exists + disabled -> SUSPENDED
                if (!$macIsEmpty && $isDisabled) {
                    if ($customer) {
                        // Update MAC and IP if different, and set to suspended
                        $updateFields = [];
                        $updateValues = [];
                        
                        // Update IP if different
                        if ($customer['ip_address'] !== $ip) {
                            $updateFields[] = "ip_address = ?";
                            $updateValues[] = $ip;
                        }
                        
                        // Update MAC if different
                        if (strtoupper(trim($customer['mac_address'] ?? '')) !== $mac) {
                            $updateFields[] = "mac_address = ?";
                            $updateValues[] = $mac;
                        }
                        
                        if ($customer['status'] !== 'suspended') {
                            $updateFields[] = "status = 'suspended'";
                        }
                        
                        if (!empty($updateFields)) {
                            $updateValues[] = $customer['id'];
                            $sql = "UPDATE customers SET " . implode(", ", $updateFields) . " WHERE id = ?";
                            $upd = $pdo->prepare($sql);
                            $upd->execute($updateValues);
                            $updated++;
                        }
                    } else {
                        // CREATE new customer with suspended status
                        $codePrefix = 'CUST';
                        $lastCode = $pdo->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1")->fetchColumn();
                        if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
                            $nextNum = (int)$matches[1] + 1;
                        } else {
                            $nextNum = 1001;
                        }
                        $customerCode = $codePrefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                        $ins = $pdo->prepare("
                            INSERT INTO customers 
                            (customer_code, name, phone, address, package_id, mac_address, ip_address, status, created_at) 
                            VALUES (?, ?, '-', ?, NULL, ?, ?, 'suspended', NOW())
                        ");
                        $ins->execute([$customerCode, $customerName, $customerAddress, $mac, $ip]);
                        $created++;
                    }
                    continue;
                }

                // RULE 1 & 4: MAC exists + enabled -> UPDATE or CREATE with active status
                if (!$macIsEmpty && !$isDisabled) {
                    if ($customer) {
                        // UPDATE existing customer
                        $updateFields = [];
                        $updateValues = [];
                        
                        // Update IP if different
                        if ($customer['ip_address'] !== $ip) {
                            $updateFields[] = "ip_address = ?";
                            $updateValues[] = $ip;
                        }
                        
                        // Update MAC if different
                        if (strtoupper(trim($customer['mac_address'] ?? '')) !== $mac) {
                            $updateFields[] = "mac_address = ?";
                            $updateValues[] = $mac;
                        }
                        
                        // If was suspended or terminated, reactivate
                        if ($customer['status'] === 'suspended') {
                            $updateFields[] = "status = 'active'";
                        } elseif ($customer['status'] === 'terminated') {
                            $updateFields[] = "status = 'pending'";
                        }
                        
                        if (!empty($updateFields)) {
                            $updateValues[] = $customer['id'];
                            $sql = "UPDATE customers SET " . implode(", ", $updateFields) . " WHERE id = ?";
                            $upd = $pdo->prepare($sql);
                            $upd->execute($updateValues);
                            $updated++;
                        }
                    } else {
                        // CREATE new customer with active status
                        $codePrefix = 'CUST';
                        $lastCode = $pdo->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1")->fetchColumn();
                        if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
                            $nextNum = (int)$matches[1] + 1;
                        } else {
                            $nextNum = 1001;
                        }
                        $customerCode = $codePrefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                        $ins = $pdo->prepare("
                            INSERT INTO customers 
                            (customer_code, name, phone, address, package_id, mac_address, ip_address, status, created_at) 
                            VALUES (?, ?, '-', ?, NULL, ?, ?, 'active', NOW())
                        ");
                        $ins->execute([$customerCode, $customerName, $customerAddress, $mac, $ip]);
                        $created++;
                    }
                }
            }

            // TERMINATE customers not found in MikroTik bindings
            // Only affect customers with non-null IP that are not already terminated
            if (!empty($processedIPs)) {
                // Create placeholders for IN clause
                $placeholders = implode(',', array_fill(0, count($processedIPs), '?'));
                
                $terminateStmt = $pdo->prepare("
                    UPDATE customers 
                    SET status = 'terminated' 
                    WHERE status != 'terminated' 
                      AND ip_address IS NOT NULL 
                      AND ip_address != ''
                      AND ip_address NOT IN ($placeholders)
                ");
                $terminateStmt->execute($processedIPs);
                $terminated = $terminateStmt->rowCount();
            }

            $pdo->commit();

            $total = count($bindings);
            $msg = "Sync selesai! Total binding: $total. Updated: $updated. Created: $created. Terminated: $terminated. Skipped: $skipped.";
            set_flash_message('success', $msg);

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Error saat sync: ' . $e->getMessage());
        }

        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();

    } elseif ($action === 'import_bindings_preview') {
        // Comprehensive migration preview - shows ALL bindings with their status
        // This is for audit purposes before/after migration
        
        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        $bindings = $api->getIpBindings();
        $api->disconnect();

        // Skip prefixes
        $skipPrefixes = ['LAP', 'Loco', 'CCTV', 'NVR', 'LIVE'];

        $results = [
            'will_update' => [],
            'will_create' => [],
            'will_terminate' => [],  // Bindings with IP but empty MAC
            'will_delete' => [],     // Customers in DB but not in MikroTik
            'will_skip' => [],
            'total' => count($bindings)
        ];

        // Track all IPs from MikroTik for DELETE detection
        $processedIPs = [];

        foreach ($bindings as $binding) {
            $mac = isset($binding['mac-address']) ? strtoupper(trim($binding['mac-address'])) : '';
            $address = isset($binding['address']) ? trim($binding['address']) : '';
            $toAddress = isset($binding['to-address']) ? trim($binding['to-address']) : '';
            $comment = isset($binding['comment']) ? trim($binding['comment']) : '';
            $isDisabled = isset($binding['disabled']) && $binding['disabled'] === 'true';
            $type = isset($binding['type']) ? $binding['type'] : '-';

            // Use address or to-address as IP for lookup
            $ip = !empty($address) ? $address : $toAddress;

            $entry = [
                'mac' => $mac ?: '-',
                'address' => $address ?: '-',
                'to_address' => $toAddress ?: '-',
                'comment' => $comment ?: '-',
                'type' => $type,
                'disabled' => $isDisabled ? 'Yes' : 'No',
                'skip_reason' => null,
                'customer' => null,
                'parsed_name' => null,
                'parsed_address' => null,
                'new_status' => $isDisabled ? 'suspended' : 'active'
            ];

            // Parse comment for name-address
            if (!empty($comment) && strpos($comment, '-') !== false) {
                $parts = explode('-', $comment, 2);
                $entry['parsed_name'] = trim($parts[0]);
                $entry['parsed_address'] = isset($parts[1]) ? trim($parts[1]) : '-';
            } elseif (!empty($comment)) {
                $entry['parsed_name'] = $comment;
                $entry['parsed_address'] = '-';
            }

            // SKIP: address AND to-address both empty (no IP = can't process)
            if (empty($address) && empty($toAddress)) {
                $entry['skip_reason'] = 'Address & To-Address kosong';
                $results['will_skip'][] = $entry;
                continue;
            }

            // Track this IP as processed
            $processedIPs[] = $ip;

            // SKIP: comment starts with skip prefixes
            $shouldSkip = false;
            foreach ($skipPrefixes as $prefix) {
                if (stripos($comment, $prefix) === 0) {
                    $entry['skip_reason'] = "Comment dimulai dengan '$prefix'";
                    $shouldSkip = true;
                    break;
                }
            }
            if ($shouldSkip) {
                $results['will_skip'][] = $entry;
                continue;
            }

            // Check if customer exists by IP address
            $stmt = $pdo->prepare("
                SELECT id, customer_code, name, mac_address, ip_address, address, status 
                FROM customers 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ip]);
            $customer = $stmt->fetch();

            // NEW RULE: If MAC is empty but IP exists -> TERMINATE
            // Check various ways MAC can be "empty": null, empty string, all zeros, whitespace, too short
            $macIsEmpty = empty(trim($mac)) 
                || strlen(trim($mac)) < 12 
                || $mac === '\\\\' 
                || preg_match('/^[0:]+$/', str_replace('-', ':', $mac))
                || $mac === '-';

            // RULE: MAC empty + disabled -> TERMINATE (for Sync) / DELETE (for Migrasi)
            if ($macIsEmpty && $isDisabled) {
                $entry['new_status'] = 'terminated/delete';
                if ($customer) {
                    $entry['customer'] = [
                        'id' => $customer['id'],
                        'code' => $customer['customer_code'],
                        'name' => $customer['name'],
                        'current_mac' => $customer['mac_address'] ? strtoupper($customer['mac_address']) : '-',
                        'current_address' => $customer['address'] ?: '-',
                        'current_status' => $customer['status']
                    ];
                    $entry['action'] = 'TERMINATE/DELETE';
                    $results['will_terminate'][] = $entry;
                } else {
                    // No customer and no MAC - skip
                    $entry['skip_reason'] = 'MAC kosong & customer tidak ditemukan';
                    $results['will_skip'][] = $entry;
                }
                continue;
            }

            // MAC empty but enabled - skip (no action)
            if ($macIsEmpty && !$isDisabled) {
                $entry['skip_reason'] = 'MAC kosong & binding enabled';
                $results['will_skip'][] = $entry;
                continue;
            }

            // RULE: MAC exists + disabled -> SUSPENDED
            if (!$macIsEmpty && $isDisabled) {
                if ($customer) {
                    $entry['customer'] = [
                        'id' => $customer['id'],
                        'code' => $customer['customer_code'],
                        'name' => $customer['name'],
                        'current_mac' => $customer['mac_address'] ? strtoupper($customer['mac_address']) : '-',
                        'current_address' => $customer['address'] ?: '-',
                        'current_status' => $customer['status']
                    ];
                    $entry['new_status'] = 'suspended';
                    $entry['action'] = 'UPDATE';
                    $results['will_update'][] = $entry;
                } else {
                    $entry['new_status'] = 'suspended';
                    $entry['action'] = 'CREATE';
                    $results['will_create'][] = $entry;
                }
                continue;
            }

            // RULE: MAC exists + enabled -> active (UPDATE or CREATE)
            if (!$macIsEmpty && !$isDisabled) {
                if ($customer) {
                    $entry['customer'] = [
                        'id' => $customer['id'],
                        'code' => $customer['customer_code'],
                        'name' => $customer['name'],
                        'current_mac' => $customer['mac_address'] ? strtoupper($customer['mac_address']) : '-',
                        'current_address' => $customer['address'] ?: '-',
                        'current_status' => $customer['status']
                    ];
                    $entry['new_status'] = 'active';
                    $entry['action'] = 'UPDATE';
                    $results['will_update'][] = $entry;
                } else {
                    $entry['new_status'] = 'active';
                    $entry['action'] = 'CREATE';
                    $results['will_create'][] = $entry;
                }
            }
        }

        // DETECT DELETE: Find customers in DB that are NOT in MikroTik bindings
        if (!empty($processedIPs)) {
            $placeholders = implode(',', array_fill(0, count($processedIPs), '?'));
            $deleteStmt = $pdo->prepare("
                SELECT id, customer_code, name, mac_address, ip_address, address, status 
                FROM customers 
                WHERE ip_address IS NOT NULL 
                  AND ip_address != ''
                  AND status != 'terminated'
                  AND ip_address NOT IN ($placeholders)
            ");
            $deleteStmt->execute($processedIPs);
            $customersToDelete = $deleteStmt->fetchAll();

            foreach ($customersToDelete as $c) {
                $results['will_delete'][] = [
                    'customer' => [
                        'id' => $c['id'],
                        'code' => $c['customer_code'],
                        'name' => $c['name'],
                        'current_mac' => $c['mac_address'] ? strtoupper($c['mac_address']) : '-',
                        'current_ip' => $c['ip_address'],
                        'current_address' => $c['address'] ?: '-',
                        'current_status' => $c['status']
                    ],
                    'reason' => 'IP tidak ditemukan di MikroTik'
                ];
            }
        }

        // Store in session for display
        $_SESSION['mikrotik_preview'] = $results;

        header("Location: ../pages/settings.php?tab=mikrotik&preview=1");
        exit();

    } elseif ($action === 'full_migration') {
        // Full migration from MikroTik IP-binding to customers
        // MikroTik is the master source
        // - Updates existing customers with IP/MAC
        // - Creates new customers from unmatched bindings
        // 
        // SKIP CRITERIA:
        // 1. address AND to-address both empty
        // 2. mac-address empty
        // 3. comment starts with: LAP, Loco, CCTV, NVR, LIVE
        //
        // SYNC CRITERIA:
        // 1. Parse comment "Name-Address" format (e.g., "Joyycell-D3/01" -> name: Joyycell, address: D3/01)
        // 2. If disabled=yes -> status = suspended
        
        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        try {
            $bindings = $api->getIpBindings();
            $api->disconnect();

            if (empty($bindings)) {
                set_flash_message('warning', 'Tidak ada IP binding ditemukan di MikroTik.');
                header("Location: ../pages/settings.php?tab=mikrotik");
                exit();
            }

            $pdo->beginTransaction();

            $updated = 0;
            $created = 0;
            $deleted = 0;
            $terminated = 0;
            $skipped = 0;
            
            // Detailed logs for audit
            $updateLog = [];
            $createLog = [];
            $deleteLog = [];
            $skipLog = [];

            // Track processed IPs for DELETE detection
            $processedIPs = [];

            // Skip prefixes (case-insensitive)
            $skipPrefixes = ['LAP', 'Loco', 'CCTV', 'NVR', 'LIVE'];

            foreach ($bindings as $binding) {
                $mac = isset($binding['mac-address']) ? strtoupper(trim($binding['mac-address'])) : '';
                $address = isset($binding['address']) ? trim($binding['address']) : '';
                $toAddress = isset($binding['to-address']) ? trim($binding['to-address']) : '';
                $comment = isset($binding['comment']) ? trim($binding['comment']) : '';
                $isDisabled = isset($binding['disabled']) && $binding['disabled'] === 'true';

                // SKIP CRITERIA 1: address AND to-address both empty (no IP = can't process)
                if (empty($address) && empty($toAddress)) {
                    $skipped++;
                    $skipLog[] = ['mac' => $mac ?: '-', 'comment' => $comment ?: '-', 'reason' => 'Address & To-Address kosong'];
                    continue;
                }

                // Use address or to-address as IP
                $ip = !empty($address) ? $address : $toAddress;

                // Track this IP as processed
                $processedIPs[] = $ip;

                // SKIP CRITERIA 2: comment starts with skip prefixes
                $shouldSkip = false;
                $skipReason = '';
                foreach ($skipPrefixes as $prefix) {
                    if (stripos($comment, $prefix) === 0) {
                        $shouldSkip = true;
                        $skipReason = "Comment '$prefix...'";
                        break;
                    }
                }
                if ($shouldSkip) {
                    $skipped++;
                    $skipLog[] = ['mac' => $mac ?: '-', 'comment' => $comment, 'reason' => $skipReason];
                    continue;
                }

                // Parse comment format: "Name-Address" (e.g., "Joyycell-D3/01")
                $customerName = '';
                $customerAddress = 'Dari MikroTik';
                
                if (!empty($comment) && strpos($comment, '-') !== false) {
                    // Split by first dash
                    $parts = explode('-', $comment, 2);
                    $customerName = trim($parts[0]);
                    if (isset($parts[1]) && !empty(trim($parts[1]))) {
                        $customerAddress = trim($parts[1]);
                    }
                } elseif (!empty($comment)) {
                    $customerName = $comment;
                } else {
                    $customerName = 'Customer-' . substr(str_replace(':', '', $mac ?: 'NONAME'), -6);
                }

                // STEP 1: Find customer by IP address (primary lookup)
                $stmt = $pdo->prepare("
                    SELECT id, customer_code, name, mac_address, ip_address, address, status 
                    FROM customers 
                    WHERE ip_address = ?
                ");
                $stmt->execute([$ip]);
                $customer = $stmt->fetch();

                // STEP 2: If not found by IP, try by name (fallback for IP mismatch cases)
                if (!$customer && !empty($customerName)) {
                    $stmt = $pdo->prepare("
                        SELECT id, customer_code, name, mac_address, ip_address, address, status 
                        FROM customers 
                        WHERE LOWER(name) = LOWER(?)
                    ");
                    $stmt->execute([$customerName]);
                    $customer = $stmt->fetch();
                }

                // Check if MAC is empty/invalid
                $macIsEmpty = empty(trim($mac)) 
                    || strlen(trim($mac)) < 12 
                    || $mac === '\\' 
                    || preg_match('/^[0:]+$/', str_replace('-', ':', $mac))
                    || $mac === '-';

                // RULE 2: MAC empty + IP exists + disabled -> DELETE from database
                if ($macIsEmpty && $isDisabled) {
                    if ($customer) {
                        // Log before deleting
                        if (count($deleteLog) < 50) {
                            $deleteLog[] = [
                                'customer_code' => $customer['customer_code'],
                                'name' => $customer['name'],
                                'ip' => $customer['ip_address'],
                                'reason' => 'MAC kosong + disabled'
                            ];
                        }
                        $del = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                        $del->execute([$customer['id']]);
                        $deleted++;
                    }
                    continue;
                }

                // RULE 3: MAC exists + IP exists + disabled -> SUSPENDED
                if (!$macIsEmpty && $isDisabled) {
                    if ($customer) {
                        // Update MAC and IP if different, and set to suspended
                        $updateFields = [];
                        $updateValues = [];
                        $updateReasons = [];
                        
                        // Update IP if different
                        if ($customer['ip_address'] !== $ip) {
                            $updateFields[] = "ip_address = ?";
                            $updateValues[] = $ip;
                            $updateReasons[] = "IP: " . ($customer['ip_address'] ?: '-') . " → $ip";
                        }
                        
                        // Update MAC if different
                        if (strtoupper(trim($customer['mac_address'] ?? '')) !== $mac) {
                            $updateFields[] = "mac_address = ?";
                            $updateValues[] = $mac;
                            $updateReasons[] = "MAC: " . ($customer['mac_address'] ?: '-') . " → $mac";
                        }
                        
                        if ($customer['status'] !== 'suspended') {
                            $updateFields[] = "status = 'suspended'";
                            $updateReasons[] = "Status → suspended";
                        }
                        
                        if (!empty($updateFields)) {
                            $updateValues[] = $customer['id'];
                            $sql = "UPDATE customers SET " . implode(", ", $updateFields) . " WHERE id = ?";
                            $upd = $pdo->prepare($sql);
                            $upd->execute($updateValues);
                            $updated++;
                            
                            if (!empty($updateReasons)) {
                                $updateLog[] = [
                                    'mac' => $mac,
                                    'comment' => $comment,
                                    'customer_code' => $customer['customer_code'],
                                    'customer_name' => $customer['name'],
                                    'changes' => implode(', ', $updateReasons)
                                ];
                            }
                        }
                    } else {
                        // CREATE new customer with suspended status
                        $codePrefix = 'CUST';
                        $lastCode = $pdo->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1")->fetchColumn();
                        if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
                            $nextNum = (int)$matches[1] + 1;
                        } else {
                            $nextNum = 1001;
                        }
                        $customerCode = $codePrefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                        $ins = $pdo->prepare("
                            INSERT INTO customers 
                            (customer_code, name, phone, address, package_id, mac_address, ip_address, status, created_at) 
                            VALUES (?, ?, '-', ?, NULL, ?, ?, 'suspended', NOW())
                        ");
                        $ins->execute([$customerCode, $customerName, $customerAddress, $mac, $ip]);
                        $created++;
                        
                        if (count($createLog) < 50) {
                            $createLog[] = [
                                'mac' => $mac,
                                'comment' => $comment,
                                'customer_code' => $customerCode,
                                'name' => $customerName,
                                'address' => $customerAddress
                            ];
                        }
                    }
                    continue;
                }

                // RULE 1 & 4: MAC exists + enabled -> UPDATE or CREATE with active status
                if (!$macIsEmpty && !$isDisabled) {
                    if ($customer) {
                        // UPDATE existing customer
                        $updateFields = [];
                        $updateValues = [];
                        $updateReasons = [];
                        
                        // Update IP if different
                        if ($customer['ip_address'] !== $ip) {
                            $updateFields[] = "ip_address = ?";
                            $updateValues[] = $ip;
                            $updateReasons[] = "IP: " . ($customer['ip_address'] ?: '-') . " → $ip";
                        }
                        
                        // Update MAC if different
                        if (strtoupper(trim($customer['mac_address'] ?? '')) !== $mac) {
                            $updateFields[] = "mac_address = ?";
                            $updateValues[] = $mac;
                            $updateReasons[] = "MAC: " . ($customer['mac_address'] ?: '-') . " → $mac";
                        }
                        
                        // Update address if current is default/empty
                        if ($customerAddress !== 'Dari MikroTik' && 
                            (empty($customer['address']) || $customer['address'] === 'Dari MikroTik')) {
                            $updateFields[] = "address = ?";
                            $updateValues[] = $customerAddress;
                            $updateReasons[] = "Address: → $customerAddress";
                        }
                        
                        // Reactivate if was suspended or terminated
                        if ($customer['status'] === 'suspended') {
                            $updateFields[] = "status = 'active'";
                            $updateReasons[] = "Status → active";
                        } elseif ($customer['status'] === 'terminated') {
                            $updateFields[] = "status = 'pending'";
                            $updateReasons[] = "Status → pending";
                        }
                        
                        if (!empty($updateFields)) {
                            $updateValues[] = $customer['id'];
                            $sql = "UPDATE customers SET " . implode(", ", $updateFields) . " WHERE id = ?";
                            $upd = $pdo->prepare($sql);
                            $upd->execute($updateValues);
                            $updated++;
                            
                            if (!empty($updateReasons)) {
                                $updateLog[] = [
                                    'mac' => $mac,
                                    'comment' => $comment,
                                    'customer_code' => $customer['customer_code'],
                                    'customer_name' => $customer['name'],
                                    'changes' => implode(', ', $updateReasons)
                                ];
                            }
                        }
                    } else {
                        // CREATE new customer with active status
                        $codePrefix = 'CUST';
                        $lastCode = $pdo->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1")->fetchColumn();
                        if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
                            $nextNum = (int)$matches[1] + 1;
                        } else {
                            $nextNum = 1001;
                        }
                        $customerCode = $codePrefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                        $ins = $pdo->prepare("
                            INSERT INTO customers 
                            (customer_code, name, phone, address, package_id, mac_address, ip_address, status, created_at) 
                            VALUES (?, ?, '-', ?, NULL, ?, ?, 'active', NOW())
                        ");
                        $ins->execute([$customerCode, $customerName, $customerAddress, $mac, $ip]);
                        $created++;
                        
                        if (count($createLog) < 50) {
                            $createLog[] = [
                                'mac' => $mac,
                                'comment' => $comment,
                                'customer_code' => $customerCode,
                                'name' => $customerName,
                                'address' => $customerAddress
                            ];
                        }
                    }
                }
            }

            // DELETE customers not found in MikroTik bindings (Migrasi Total = full sync including delete)
            if (!empty($processedIPs)) {
                $placeholders = implode(',', array_fill(0, count($processedIPs), '?'));
                
                // Find customers to delete
                $findDeleteStmt = $pdo->prepare("
                    SELECT id, customer_code, name, ip_address 
                    FROM customers 
                    WHERE ip_address IS NOT NULL 
                      AND ip_address != ''
                      AND ip_address NOT IN ($placeholders)
                ");
                $findDeleteStmt->execute($processedIPs);
                $customersToDelete = $findDeleteStmt->fetchAll();

                foreach ($customersToDelete as $c) {
                    if (count($deleteLog) < 50) {
                        $deleteLog[] = [
                            'customer_code' => $c['customer_code'],
                            'name' => $c['name'],
                            'ip' => $c['ip_address']
                        ];
                    }
                }

                // Actually DELETE the customers
                $deleteStmt = $pdo->prepare("
                    DELETE FROM customers 
                    WHERE ip_address IS NOT NULL 
                      AND ip_address != ''
                      AND ip_address NOT IN ($placeholders)
                ");
                $deleteStmt->execute($processedIPs);
                $deleted = $deleteStmt->rowCount();
            }

            $pdo->commit();

            $total = count($bindings);
            $msg = "Migrasi selesai! Total binding: $total. Updated: $updated. Created: $created. Deleted: $deleted. Terminated: $terminated. Skipped: $skipped.";
            set_flash_message('success', $msg);
            
            // Store detailed log in session for audit
            $_SESSION['migration_log'] = [
                'total' => $total,
                'updated' => $updated,
                'created' => $created,
                'deleted' => $deleted,
                'terminated' => $terminated,
                'skipped' => $skipped,
                'update_log' => $updateLog,
                'create_log' => $createLog,
                'delete_log' => $deleteLog,
                'skip_log' => array_slice($skipLog, 0, 50) // Limit to 50
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Error saat migrasi: ' . $e->getMessage());
        }

        header("Location: ../pages/settings.php?tab=mikrotik&migration=1");
        exit();

    } elseif ($action === 'import_selected_bindings') {
        // Import selected unmatched bindings as new customers
        $selectedMacs = $_POST['selected_macs'] ?? [];
        
        if (empty($selectedMacs)) {
            set_flash_message('error', 'Tidak ada binding yang dipilih.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        $api = getMikrotikApiFromSettings($pdo);
        
        if (!$api) {
            set_flash_message('error', 'MikroTik tidak terkonfigurasi atau koneksi gagal.');
            header("Location: ../pages/settings.php?tab=mikrotik");
            exit();
        }

        try {
            $bindings = $api->getIpBindings();
            $api->disconnect();

            // Get default package
            $defaultPackage = $pdo->query("SELECT id FROM internet_packages ORDER BY id ASC LIMIT 1")->fetchColumn();
            if (!$defaultPackage) {
                throw new Exception('Tidak ada paket internet tersedia.');
            }

            $pdo->beginTransaction();
            $created = 0;

            foreach ($bindings as $binding) {
                if (!isset($binding['mac-address'])) continue;
                
                $mac = strtoupper(trim($binding['mac-address']));
                
                if (!in_array($mac, $selectedMacs)) continue;

                $ip = isset($binding['address']) ? trim($binding['address']) : null;
                $comment = isset($binding['comment']) ? trim($binding['comment']) : '';

                // Check if already exists
                $stmt = $pdo->prepare("SELECT id FROM customers WHERE UPPER(mac_address) = ?");
                $stmt->execute([$mac]);
                if ($stmt->fetch()) continue; // Skip if exists

                // Generate customer code
                $lastCode = $pdo->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1")->fetchColumn();
                if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
                    $nextNum = (int)$matches[1] + 1;
                } else {
                    $nextNum = 1001;
                }
                $customerCode = 'CUST' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                $customerName = !empty($comment) ? $comment : 'Customer-' . substr(str_replace(':', '', $mac), -6);

                $ins = $pdo->prepare("
                    INSERT INTO customers 
                    (customer_code, name, phone, address, package_id, mac_address, ip_address, status, created_at) 
                    VALUES (?, ?, '-', 'Dari MikroTik', ?, ?, ?, 'active', NOW())
                ");
                $ins->execute([$customerCode, $customerName, $defaultPackage, $mac, $ip]);
                $created++;
            }

            $pdo->commit();
            set_flash_message('success', "Berhasil membuat $created customer baru dari binding.");

        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Error: ' . $e->getMessage());
        }

        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();
    }
    } catch (Throwable $t) {
        // Log error for admin
        error_log("MikroTik Action Error: " . $t->getMessage());
        
        // Set error for Modal display
        $_SESSION['mikrotik_error_details'] = $t->getMessage();
        
        // Return to settings page
        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();
    }
}

// Invalid request
header("Location: ../pages/settings.php");
exit();
?>
