<?php
// cron_generate_invoices.php
// Can be called via CLI cron or HTTP Request by Admin
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/MikrotikApi.php';

// If called via HTTP, ensure Admin/Finance
if (isset($_SERVER['REQUEST_METHOD'])) {
    require_login();
    require_role(['Administrator', 'Finance']);
}

$month = date('n');
$year = date('Y');
$today = date('Y-m-d');
$generated_count = 0;
$overdue_count = 0;
$mikrotik_synced = 0;

try {
    $pdo->beginTransaction();

    // 1. Generate Invoices for Active Customers
    // Check customers who don't have invoice for this month/year
    $sql = "SELECT c.*, p.price 
            FROM customers c 
            JOIN internet_packages p ON c.package_id = p.id 
            WHERE c.status = 'active'
            AND c.id NOT IN (
                SELECT customer_id FROM invoices 
                WHERE period_month = ? AND period_year = ?
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$month, $year]);
    $customers = $stmt->fetchAll();

    foreach ($customers as $c) {
        $inv_number = 'INV/' . date('Ym') . '/' . $c['customer_code'];
        // Due date: 10th of this month (or next if generated late? Assuming generated on 1st, due on 10th)
        $due_date = date('Y-m') . '-' . str_pad($c['due_date_day'], 2, '0', STR_PAD_LEFT);

        $ins = $pdo->prepare("INSERT INTO invoices (invoice_number, customer_id, period_month, period_year, amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?, 'unpaid')");
        $ins->execute([$inv_number, $c['id'], $month, $year, $c['price'], $due_date]);
        $generated_count++;
    }

    // 2. Update Overdue Status
    // Get customers that will be marked overdue (before updating)
    $overdueCustomers = $pdo->prepare("SELECT DISTINCT customer_id FROM invoices WHERE status = 'unpaid' AND due_date < ?");
    $overdueCustomers->execute([$today]);
    $overdueCustomerIds = $overdueCustomers->fetchAll(PDO::FETCH_COLUMN);

    // If due_date < today AND status = unpaid/pending? (Usually pending doesn't go overdue if they uploaded proof, but let's stick to simple: unpaid goes overdue)
    $upd = $pdo->prepare("UPDATE invoices SET status = 'overdue' WHERE status = 'unpaid' AND due_date < ?");
    $upd->execute([$today]);
    $overdue_count = $upd->rowCount();

    $pdo->commit();

    // 3. MikroTik: Disable bindings for newly overdue customers
    if ($overdue_count > 0 && !empty($overdueCustomerIds)) {
        $api = getMikrotikApiFromSettings($pdo);
        if ($api) {
            foreach ($overdueCustomerIds as $custId) {
                $result = syncCustomerBinding($pdo, $api, $custId);
                if (isset($result['success']) && $result['success']) {
                    $mikrotik_synced++;
                }
            }
            $api->disconnect();
        }
    }

    if (isset($_SERVER['REQUEST_METHOD'])) {
        $msg = "Generate Selesai. $generated_count Invoice dibuat. $overdue_count Invoice marked overdue.";
        if ($mikrotik_synced > 0) {
            $msg .= " $mikrotik_synced MikroTik binding disinkronkan.";
        }
        set_flash_message('success', $msg);
        header("Location: ../pages/invoices.php");
        exit();
    } else {
        echo "Done. Generated: $generated_count. Overdue: $overdue_count. MikroTik Synced: $mikrotik_synced.\n";
    }

} catch (Exception $e) {
    $pdo->rollBack();
    if (isset($_SERVER['REQUEST_METHOD'])) {
        set_flash_message('error', "Error: " . $e->getMessage());
        header("Location: ../pages/invoices.php");
        exit();
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>

