<?php
/**
 * Cron Job: MikroTik IP Binding Sync
 * 
 * Syncs IP binding disabled status based on customer payment status
 * Should be run periodically (e.g., every hour or after invoice cron)
 * 
 * Usage:
 *   CLI: php cron_mikrotik_sync.php
 *   HTTP: Access via browser (requires admin login)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/MikrotikApi.php';

// If called via HTTP, ensure Admin access
if (isset($_SERVER['REQUEST_METHOD'])) {
    require_login();
    require_role(['Administrator']);
}

$startTime = microtime(true);
$results = [
    'total' => 0,
    'synced' => 0,
    'skipped' => 0,
    'failed' => 0,
    'details' => []
];

// Get MikroTik API connection
$api = getMikrotikApiFromSettings($pdo);

if (!$api) {
    $message = "MikroTik sync disabled or connection failed.";
    if (isset($_SERVER['REQUEST_METHOD'])) {
        set_flash_message('error', $message);
        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();
    } else {
        echo $message . "\n";
        exit(1);
    }
}

try {
    // Get all active customers with MAC addresses
    $stmt = $pdo->query("
        SELECT c.id, c.customer_code, c.name, c.mac_address, c.status,
               (SELECT COUNT(*) FROM invoices i WHERE i.customer_id = c.id AND i.status = 'overdue') as overdue_count
        FROM customers c
        WHERE c.mac_address IS NOT NULL 
          AND c.mac_address != '' 
          AND c.mac_address != '\\\\\\\\'
          AND LENGTH(c.mac_address) >= 12
    ");
    $customers = $stmt->fetchAll();

    foreach ($customers as $customer) {
        $results['total']++;
        
        $mac = trim($customer['mac_address']);
        $shouldDisable = ($customer['overdue_count'] > 0) || ($customer['status'] !== 'active');
        
        // Find binding
        $binding = $api->findBindingByMac($mac);
        
        if (!$binding) {
            $results['skipped']++;
            $results['details'][] = [
                'customer' => $customer['name'],
                'mac' => $mac,
                'status' => 'skipped',
                'reason' => 'Binding not found in MikroTik'
            ];
            continue;
        }

        $currentlyDisabled = isset($binding['disabled']) && $binding['disabled'] === 'true';
        
        // Only update if state needs to change
        if ($shouldDisable !== $currentlyDisabled) {
            if ($shouldDisable) {
                $success = $api->setBindingDisabled($binding['.id'], true);
                $action = 'disabled';
            } else {
                $success = $api->setBindingDisabled($binding['.id'], false);
                $action = 'enabled';
            }

            if ($success) {
                $results['synced']++;
                $results['details'][] = [
                    'customer' => $customer['name'],
                    'mac' => $mac,
                    'status' => 'synced',
                    'action' => $action
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'customer' => $customer['name'],
                    'mac' => $mac,
                    'status' => 'failed',
                    'reason' => $api->getLastError()
                ];
            }
        } else {
            $results['skipped']++;
            $results['details'][] = [
                'customer' => $customer['name'],
                'mac' => $mac,
                'status' => 'skipped',
                'reason' => 'Already in correct state'
            ];
        }
    }

} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    if (isset($_SERVER['REQUEST_METHOD'])) {
        set_flash_message('error', $message);
        header("Location: ../pages/settings.php?tab=mikrotik");
        exit();
    } else {
        echo $message . "\n";
        exit(1);
    }
} finally {
    $api->disconnect();
}

$elapsed = round(microtime(true) - $startTime, 2);

// Output results
if (isset($_SERVER['REQUEST_METHOD'])) {
    $msg = "Sync selesai dalam {$elapsed}s. Total: {$results['total']}, Synced: {$results['synced']}, Skipped: {$results['skipped']}, Failed: {$results['failed']}";
    set_flash_message('success', $msg);
    header("Location: ../pages/settings.php?tab=mikrotik");
    exit();
} else {
    echo "MikroTik Sync Complete\n";
    echo "=====================\n";
    echo "Time: {$elapsed}s\n";
    echo "Total customers: {$results['total']}\n";
    echo "Synced: {$results['synced']}\n";
    echo "Skipped: {$results['skipped']}\n";
    echo "Failed: {$results['failed']}\n";
    echo "\nDetails:\n";
    foreach ($results['details'] as $detail) {
        echo "  - {$detail['customer']} ({$detail['mac']}): {$detail['status']}";
        if (isset($detail['action'])) echo " -> {$detail['action']}";
        if (isset($detail['reason'])) echo " ({$detail['reason']})";
        echo "\n";
    }
}
?>
