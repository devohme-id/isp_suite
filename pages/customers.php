<?php
require_once '../config.php';
require_login();
$page_title = 'Data Pelanggan';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Pagination & Filter Logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? '';
$search = $_GET['q'] ?? '';

// Base Query - Use LEFT JOIN to include customers without packages
$sql_base = "FROM customers c LEFT JOIN internet_packages p ON c.package_id = p.id WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql_base .= " AND c.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql_base .= " AND (c.name LIKE ? OR c.customer_code LIKE ? OR c.address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count Total
$stmt_count = $pdo->prepare("SELECT COUNT(*) " . $sql_base);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Data
$sql = "SELECT c.*, p.package_name, p.price " . $sql_base . " ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Fetch Packages for Dropdown
$packages = $pdo->query("SELECT * FROM internet_packages WHERE is_active=1")->fetchAll();

// Fetch Drop Points (DP) for Dropdown
$dps = $pdo->query("SELECT * FROM drop_points ORDER BY dp_code ASC, dp_name ASC")->fetchAll();

// Statistics Calculation
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'pending'")->fetchColumn(),
    'inactive' => $pdo->query("SELECT COUNT(*) FROM customers WHERE status IN ('suspended', 'terminated')")->fetchColumn(),
];

// Get 5 Most Recent Customer IDs
$newest_ids = $pdo->query("SELECT id FROM customers ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);

// Get IDs of displayed customers
$customer_ids = array_column($customers, 'id');
$cust_invoice_status = [];

if (!empty($customer_ids)) {
    $placeholders = str_repeat('?,', count($customer_ids) - 1) . '?';
    // Fetch relevant invoices
    $sqlInv = "SELECT customer_id, status, due_date FROM invoices 
               WHERE customer_id IN ($placeholders) 
               AND status IN ('unpaid', 'overdue', 'pending')";
    $stmtInv = $pdo->prepare($sqlInv);
    $stmtInv->execute($customer_ids);
    $rows = $stmtInv->fetchAll(PDO::FETCH_ASSOC);

    // Group by customer
    $grouped = [];
    foreach ($rows as $r) {
        $grouped[$r['customer_id']][] = $r;
    }

    // Determine priority status
    foreach ($customer_ids as $cid) {
        if (!isset($grouped[$cid])) {
            $cust_invoice_status[$cid] = 'paid'; // No outstanding invoices
            continue;
        }
        
        $status = 'paid';
        $hasOverdue = false;
        $hasUnpaid = false;
        $hasPending = false;

        foreach ($grouped[$cid] as $inv) {
            if ($inv['status'] == 'overdue' || ($inv['status'] == 'unpaid' && $inv['due_date'] < date('Y-m-d'))) {
                $hasOverdue = true;
            } elseif ($inv['status'] == 'unpaid') {
                $hasUnpaid = true;
            } elseif ($inv['status'] == 'pending') {
                $hasPending = true;
            }
        }

        if ($hasOverdue) $cust_invoice_status[$cid] = 'overdue';
        elseif ($hasUnpaid) $cust_invoice_status[$cid] = 'unpaid';
        elseif ($hasPending) $cust_invoice_status[$cid] = 'pending';
        else $cust_invoice_status[$cid] = 'paid';
    }
}
// Legacy overdue_ids used for reminder button, keep it populated based on new logic or just fetch simple again if needed.
// Actually, let's keep overdue_ids for backward compatibility with the reminder button below, 
// OR simpler: recalculate it from the fetched data to save a query.
$overdue_ids = [];
foreach ($cust_invoice_status as $cid => $status) {
    if ($status === 'overdue') {
        $overdue_ids[] = $cid;
    }
}
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 border-b border-gray-200 pb-4">
            <div>
                 <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Pelanggan</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                 <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Pelanggan</h1>
                 <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manajemen data pelanggan dan layanan.</p>
            </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" onclick="openModal('addCustomerModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-all">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Tambah Pelanggan
                    </button>
                    <button type="button" onclick="openModal('importCustomerModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white hover:bg-gray-50 dark:hover:bg-slate-700 shadow-sm transition-all">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                        Import Data
                    </button>
                </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Card 1: Total Pelanggan -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Total Pelanggan</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white"><?= $stats['total'] ?></h3>
                </div>
            </div>
            <!-- Card 2: Aktif -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-green-600 font-bold">Aktif</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white"><?= $stats['active'] ?></h3>
                </div>
            </div>
            <!-- Card 3: Pending -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-yellow-500 font-bold">Pending (Baru)</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white"><?= $stats['pending'] ?></h3>
                </div>
            </div>
            <!-- Card 4: Non-Aktif -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-red-500 font-bold">Non-Aktif / Suspend</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white"><?= $stats['inactive'] ?></h3>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                         <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" name="q" placeholder="Cari nama, kode pelanggan..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
                </div>
                <div class="sm:w-48">
                    <select name="status" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending (Baru)</option>
                        <option value="suspended" <?= $status_filter == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="terminated" <?= $status_filter == 'terminated' ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                    Filter Data
                </button>
            </form>
        </div>



        <?php if (isset($_SESSION['whatsapp_link'])): ?>
            <script>
                // Open WhatsApp in new tab
                window.open("<?= $_SESSION['whatsapp_link'] ?>", "_blank");
            </script>
            <?php unset($_SESSION['whatsapp_link']); ?>
        <?php endif; ?>

        <!-- Table Section -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <!-- Table Header -->
            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div>
                   <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Pelanggan</h2>
                </div>
                <div>
                    <div class="inline-flex gap-x-2">
                        <!-- Export actions could go here -->
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-[#F8FAFC] dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Info Pelanggan</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Layanan</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Addr (ONT)</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tagihan</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php if (count($customers) > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($customers as $c): ?>
                            <tr class="hover:bg-[#F8FAFC]/50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $no++ ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-x-3">
                                        <div class="flex-shrink-0 flex justify-center items-center size-[38px] bg-[#F8FAFC] dark:bg-slate-700 rounded-full text-gray-500 dark:text-gray-400 font-bold text-xs uppercase">
                                            <?= substr($c['name'], 0, 2) ?>
                                        </div>
                                        <div class="grow">
                                            <div class="flex items-center gap-x-2">
                                                <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($c['name']) ?></span>
                                                <?php if(in_array($c['id'], $newest_ids)): ?>
                                                    <span class="inline-flex items-center gap-x-1.5 py-0.5 px-2 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        New
                                                    </span>
                                                <?php endif; ?>
                                                <?php
                                                    $wa_phone = preg_replace('/[^0-9]/', '', $c['phone']);
                                                    if(substr($wa_phone, 0, 1) === '0') $wa_phone = '62' . substr($wa_phone, 1);
                                                ?>
                                                <a href="https://wa.me/<?= $wa_phone ?>" target="_blank" class="text-green-500 hover:text-green-700 transition-colors" title="Chat WhatsApp">
                                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                </a>
                                            </div>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="<?= htmlspecialchars($c['address']) ?>"><?= htmlspecialchars($c['address']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($c['package_name'])): ?>
                                        <div class="block text-sm font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($c['package_name']) ?></div>
                                        <div class="block text-xs text-blue-600 dark:text-blue-400 font-semibold"><?= format_rupiah($c['price']) ?></div>
                                    <?php else: ?>
                                        <div class="block text-sm text-gray-400 italic">Belum dipilih</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-between gap-x-4">
                                        <div class="flex flex-col">
                                            <?php if(!empty($c['ip_address'])): ?>
                                                <span class="text-sm font-semibold text-gray-800 dark:text-white font-mono"><?= htmlspecialchars($c['ip_address']) ?></span>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-400 italic">No IP</span>
                                            <?php endif; ?>

                                            <?php if(!empty($c['mac_address'])): ?>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono"><?= htmlspecialchars($c['mac_address']) ?></span>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">-</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if(!empty($c['ip_address'])): ?>
                                            <a href="http://<?= htmlspecialchars($c['ip_address']) ?>" target="_blank" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors" title="Remote ONT">
                                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                                                Remote
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $display_status = $c['status'];
                                        $badge_class = '';
                                        
                                        // Status logic simplified (Invoice status moved to Tagihan column)
                                        $badge_class = match($c['status']) {
                                            'active' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'suspended' => 'bg-red-100 text-red-800',
                                            'terminated' => 'bg-gray-100 text-gray-800',
                                            default => 'bg-blue-100 text-blue-800'
                                        };
                                    ?>
                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium <?= $badge_class ?>">
                                        <?= ucfirst($display_status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $invStatus = $cust_invoice_status[$c['id']] ?? 'paid';
                                        $invBadgeClass = match($invStatus) {
                                            'paid' => 'bg-teal-100 text-teal-800',
                                            'unpaid' => 'bg-orange-100 text-orange-800',
                                            'overdue' => 'bg-red-100 text-red-800',
                                            'pending' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        $invText = match($invStatus) {
                                            'paid' => 'Lunas',
                                            'unpaid' => 'Belum Bayar',
                                            'overdue' => 'Nunggak',
                                            'pending' => 'Menunggu Verifikasi',
                                            default => '-'
                                        };
                                    ?>
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center gap-x-1 px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide <?= $invBadgeClass ?>">
                                            <?= $invText ?>
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Jatuh Tempo: <b class="text-gray-800 dark:text-gray-200"><?= $c['due_date_day'] ?></b></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end">
                                    <a href="customer_detail.php?id=<?= $c['id'] ?>" class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Detail
                                    </a>
                                    
                                    <?php if(in_array($c['id'], $overdue_ids)): ?>
                                        <?php
                                            $reminder_phone = preg_replace('/[^0-9]/', '', $c['phone']);
                                            if(substr($reminder_phone, 0, 1) === '0') $reminder_phone = '62' . substr($reminder_phone, 1);
                                            $reminder_msg = "Halo {$c['name']}, kami mengingatkan bahwa status tagihan internet Anda saat ini *Lewat Jatuh Tempo / Nunggak*. Mohon segera lakukan pembayaran. Terima kasih.";
                                            $reminder_link = "https://wa.me/$reminder_phone?text=" . urlencode($reminder_msg);
                                        ?>
                                        <a href="<?= $reminder_link ?>" target="_blank" class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-yellow-500 text-white shadow-sm hover:bg-yellow-600 transition-all mr-2" title="Kirim Reminder Invoice">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                            Reminder
                                        </a>
                                    <?php endif; ?>

                                    <?php if($c['status'] !== 'pending'): ?>
                                        <button type="button"  
                                            class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 btn-edit"
                                            data-id="<?= $c['id'] ?>"
                                            data-name="<?= htmlspecialchars($c['name'] ?? '') ?>"
                                            data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>"
                                            data-address="<?= htmlspecialchars($c['address'] ?? '') ?>"
                                            data-package="<?= $c['package_id'] ?>"
                                            data-lat="<?= $c['latitude'] ?>"
                                            data-long="<?= $c['longitude'] ?>"
                                            data-status="<?= $c['status'] ?>"
                                            data-mac="<?= htmlspecialchars($c['mac_address'] ?? '') ?>"
                                            data-ip="<?= htmlspecialchars($c['ip_address'] ?? '') ?>"
                                            data-dp="<?= $c['dp_id'] ?? '' ?>"
                                            data-port="<?= $c['dp_port'] ?? '' ?>">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                            Edit
                                        </button>
                                    <?php else: ?>
                                        <button type="button" disabled class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-100 bg-gray-50 text-gray-300 shadow-sm cursor-not-allowed" title="Approve terlebih dahulu">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                            Edit
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if($c['status'] === 'pending'): ?>
                                        <button type="button" 
                                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white shadow-sm hover:bg-green-700 transition-all mr-2 btn-approve-action"
                                            data-id="<?= $c['id'] ?>"
                                            data-name="<?= htmlspecialchars($c['name']) ?>"
                                            data-phone="<?= htmlspecialchars($c['phone']) ?>"
                                            onclick="openApproveModal(this)">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Approve
                                        </button>
                                    <?php endif; ?>

                                    <?php if($_SESSION['role'] === 'Administrator'): ?>
                                    <button type="button" onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>')" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none transition-all ms-2">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                            Hapus
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center">
                                    <div class="flex flex-col justify-center items-center">
                                        <div class="size-12 rounded-full bg-[#F8FAFC] flex items-center justify-center mb-3">
                                            <svg class="size-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        </div>
                                        <h3 class="text-gray-800 font-semibold">Tidak ada data ditemukan</h3>
                                        <p class="text-gray-500 text-sm mt-1">Coba sesuaikan filter atau kata kunci pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-slate-700">
                <div>
                     <p class="text-sm text-gray-600 dark:text-gray-400">
                        Menampilkan <span class="font-semibold text-gray-800 dark:text-white"><?= $offset + 1 ?></span> - <span class="font-semibold text-gray-800 dark:text-white"><?= min($offset + $limit, $total_records) ?></span> dari <span class="font-semibold text-gray-800 dark:text-white"><?= $total_records ?></span> data
                    </p>
                </div>
                <div>
                    <?= render_pagination($total_pages, $page) ?>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 text-gray-400 text-xs">
            <p>&copy; <?= date('Y') ?> ISP Billing System. Developed with TailPanel Style.</p>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Tambah Pelanggan Baru</h3>
            <button type="button" onclick="closeModal('addCustomerModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/customer_actions.php" method="POST" class="p-6 overflow-y-auto">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid gap-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Lengkap</label>
                    <input type="text" name="name" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                </div>

                <div>
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Paket Internet</label>
                     <select name="package_id" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required onchange="toggleCustomPrice(this)">
                        <option value="">Pilih Paket</option>
                        <?php foreach($packages as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['package_name']) ?> - Rp <?= number_format($p['price'], 0, ',', '.') ?></option>
                        <?php endforeach; ?>
                        <option value="custom">Custom (Tentukan Sendiri)</option>
                     </select>
                </div>
                
                <div id="custom_price_div" class="hidden">
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Harga Custom (Rp)</label>
                     <input type="number" name="custom_price" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Masukkan harga bulanan">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Alamat Pemasangan</label>
                    <textarea name="address" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" rows="2" required></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">IP Address</label>
                        <input type="text" name="ip_address" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="192.168.x.x">
                    </div>
                     <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">MAC Address</label>
                        <input type="text" name="mac_address" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="AA:BB:CC:DD:EE:FF">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                         <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Drop Point (DP)</label>
                         <select name="dp_id" id="add_dp_id" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                            <option value="">Pilih Drop Point</option>
                            <?php foreach($dps as $dp): ?>
                                <option value="<?= $dp['id'] ?>"><?= htmlspecialchars($dp['dp_code'] . ' - ' . $dp['dp_name']) ?></option>
                            <?php endforeach; ?>
                         </select>
                    </div>
                     <div>
                         <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Port Drop Point</label>
                         <select name="dp_port" id="add_dp_port" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" disabled>
                            <option value="">Pilih Port</option>
                         </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">No. HP / WA</label>
                    <input type="text" name="phone_number" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="08123456789">
                </div>
                 <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Tanggal Install</label>
                    <input type="date" name="installation_date" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="flex justify-end gap-x-2 pt-4 border-t border-gray-200 dark:border-slate-700 mt-4">
                 <button type="button" onclick="closeModal('addCustomerModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                    Simpan Pelanggan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Data Pelanggan</h3>
            <button type="button" onclick="closeModal('editCustomerModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/customer_actions.php" method="POST" class="p-6 overflow-y-auto">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid gap-y-4">
                <div>
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Lengkap</label>
                     <input type="text" name="name" id="edit_name" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                </div>
                <div>
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Email (Opsional)</label>
                     <input type="email" name="email" id="edit_email" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">No. HP / WA</label>
                     <input type="text" name="phone_number" id="edit_phone" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                </div>
                 <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Alamat</label>
                    <textarea name="address" id="edit_address" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" rows="2" required></textarea>
                </div>
                
                 <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">IP Address</label>
                        <input type="text" name="ip_address" id="edit_ip" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                    </div>
                     <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">MAC Address</label>
                        <input type="text" name="mac_address" id="edit_mac" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                         <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Drop Point (DP)</label>
                         <select name="dp_id" id="edit_dp_id" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                            <option value="">Pilih Drop Point</option>
                            <?php foreach($dps as $dp): ?>
                                <option value="<?= $dp['id'] ?>"><?= htmlspecialchars($dp['dp_code'] . ' - ' . $dp['dp_name']) ?></option>
                            <?php endforeach; ?>
                         </select>
                    </div>
                     <div>
                         <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Port Drop Point</label>
                         <select name="dp_port" id="edit_dp_port" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                            <option value="">Pilih Port</option>
                         </select>
                    </div>
                </div>

                 <div>
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Paket Internet</label>
                     <select name="package_id" id="edit_package_id" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                        <option value="">Pilih Paket</option>
                        <?php foreach($packages as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['package_name']) ?></option>
                        <?php endforeach; ?>
                        <option value="custom">Custom</option>
                     </select>
                </div>
                


                 <div>
                     <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Status</label>
                     <select name="status" id="edit_status" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                     </select>
                </div>
            </div>

            <div class="flex justify-end gap-x-2 pt-4 border-t border-gray-200 dark:border-slate-700 mt-4">
                 <button type="button" onclick="closeModal('editCustomerModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                    Update Data
                </button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- Import Customer Modal -->
<div id="importCustomerModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Import Data Pelanggan</h3>
            <button type="button" onclick="closeModal('importCustomerModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/import_customers_action.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-white">File Excel (.xlsx, .xls)</label>
                    <a href="../templates/template_import_pelanggan.xlsx" download class="py-1 px-2.5 inline-flex items-center gap-x-1 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700">
                         <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        Download Template
                    </a>
                </div>
                <input type="file" name="file" class="block w-full border border-gray-200 dark:border-slate-700 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 file:bg-[#F8FAFC] dark:file:bg-slate-700 dark:file:text-gray-300 file:border-0 file:me-4 file:py-3 file:px-4" required accept=".xlsx, .xls">
                 <p class="text-xs text-gray-500 mt-2">Pastikan format file sesuai dengan template.</p>
            </div>
            
            <div class="flex justify-end gap-x-2 pt-4 border-t border-gray-200 dark:border-slate-700">
                 <button type="button" onclick="closeModal('importCustomerModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                    Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Edit Button Logic
        const editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_email').value = btn.dataset.email;
                document.getElementById('edit_phone').value = btn.dataset.phone;
                document.getElementById('edit_address').value = btn.dataset.address;
                document.getElementById('edit_mac').value = btn.dataset.mac || '';
                document.getElementById('edit_ip').value = btn.dataset.ip || '';
                document.getElementById('edit_package_id').value = btn.dataset.package;

                document.getElementById('edit_status').value = btn.dataset.status;
                document.getElementById('edit_dp_id').value = btn.dataset.dp;
                
                // Load ports for edit (passing current port & customer ID to allow self-selection)
                loadPorts(btn.dataset.dp, document.getElementById('edit_dp_port'), btn.dataset.port, btn.dataset.id);

                openModal('editCustomerModal');
            });
        });

        // DP Port Logic
        const addDpSelect = document.getElementById('add_dp_id');
        const addPortSelect = document.getElementById('add_dp_port');
        
        const editDpSelect = document.getElementById('edit_dp_id');
        const editPortSelect = document.getElementById('edit_dp_port');

        addDpSelect.addEventListener('change', function() {
            loadPorts(this.value, addPortSelect);
        });

        editDpSelect.addEventListener('change', function() {
            // When user manually changes DP in Edit Mode, reset selected port
            loadPorts(this.value, editPortSelect);
        });

        function loadPorts(dpId, targetSelect, currentPort = null, currentCustomerId = null) {
            if (!dpId) {
                targetSelect.innerHTML = '<option value="">-- Pilih Drop Point Terlebih Dahulu --</option>';
                targetSelect.disabled = true;
                return;
            }

            targetSelect.disabled = true;
            targetSelect.innerHTML = '<option value="">Memuat port...</option>';

            fetch(`../actions/ftth_data.php?action=get_dp_ports&dp_id=${dpId}`)
                .then(response => response.json())
                .then(data => {
                    targetSelect.innerHTML = '<option value="">-- Pilih Port --</option>';
                    if (data.success) {
                        for (let i = 1; i <= data.total_ports; i++) {
                            let option = document.createElement('option');
                            option.value = i;
                            
                            // Check occupation
                            if (data.occupied_ports[i]) {
                                let occupant = data.occupied_ports[i];
                                // If occupied by SOMEONE ELSE (not the current customer if editing)
                                if (currentCustomerId && occupant.customer_id == currentCustomerId) {
                                     option.text = `Port ${i} (Saat Ini)`;
                                     option.selected = true;
                                } else {
                                    option.text = `Port ${i} (Terisi: ${occupant.name})`;
                                    option.disabled = true;
                                    option.classList.add('text-red-500');
                                }
                            } else {
                                option.text = `Port ${i} (Kosong)`;
                                if (currentPort && i == currentPort) {
                                    option.selected = true;
                                }
                            }
                            targetSelect.appendChild(option);
                        }
                        targetSelect.disabled = false;
                    } else {
                         targetSelect.innerHTML = '<option value="">Gagal memuat data</option>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    targetSelect.innerHTML = '<option value="">Error koneksi</option>';
                    targetSelect.disabled = false;
                });
        }
    });

    // Direct Function for Opening Modal
    function openApproveModal(btn) {
        // Prevent event bubbling
        event.stopPropagation();
        
        // Populate Data
        if(document.getElementById('approve_id')) document.getElementById('approve_id').value = btn.dataset.id;
        if(document.getElementById('approve_name')) document.getElementById('approve_name').innerText = btn.dataset.name;
        if(document.getElementById('wa_phone')) document.getElementById('wa_phone').value = btn.dataset.phone;

        openModal('manualApproveModal');
    }

    function submitApproveForm() {
        const form = document.getElementById('approve_form');
        if(form.reportValidity()) {
            form.submit();
        }
    }
</script>

<!-- Manual Approve Modal (Standardized) -->
<div id="manualApproveModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
         <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700 bg-green-600 rounded-t-xl">
            <h3 class="font-bold text-white">Approve Pelanggan</h3>
            <button type="button" onclick="closeModal('manualApproveModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-green-700 text-white hover:bg-green-800 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <form action="../actions/customer_actions.php" method="POST" id="approve_form">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" id="approve_id">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" id="wa_phone"> 

                <div class="mb-4">
                    <p class="text-sm text-gray-800 dark:text-gray-200">Menyetujui pendaftaran pelanggan: <strong id="approve_name" class="dark:text-white"></strong></p>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Jadwal Instalasi</label>
                    <input type="datetime-local" name="installation_date" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]" required>
                </div>

                <div class="flex justify-end gap-x-2 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="closeModal('manualApproveModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                        Batal
                    </button>
                    <button type="button" onclick="submitApproveForm()" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700">
                        CONFIRM Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_name').innerText = name;
        openModal('confirmDeleteModal');
    }
</script>

<!-- Confirm Delete Modal -->
<div id="confirmDeleteModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-sm w-full">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                <svg class="size-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Apakah anda yakin ingin menghapus pelanggan <strong id="delete_name" class="text-gray-800 dark:text-white"></strong>? Data yang dihapus tidak dapat dikembalikan.
            </p>
            <form action="../actions/customer_actions.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="flex justify-center gap-3">
                    <button type="button" onclick="closeModal('confirmDeleteModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                        Batal
                    </button>
                    <button type="submit" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 shadow-sm">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
