<?php
require_once '../config.php';
require_login();
$page_title = 'Tagihan & Keuangan';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Pagination & Filter Logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status = $_GET['status'] ?? '';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$search = clean_input($_GET['search'] ?? '');

// Base Query
$sql_base = "FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE 1=1";
$params = [];

if ($status) {
    if ($status === 'pending_verif') {
        $sql_base .= " AND i.status = 'pending'";
    } else {
        $sql_base .= " AND i.status = ?";
        $params[] = $status;
    }
}
if ($month) {
    $sql_base .= " AND i.period_month = ?";
    $params[] = $month;
}
if ($year) {
    $sql_base .= " AND i.period_year = ?";
    $params[] = $year;
}
if ($search) {
    $sql_base .= " AND (c.name LIKE ? OR c.address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count Total
$stmt_count = $pdo->prepare("SELECT COUNT(*) " . $sql_base);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Data
// Fetch Data
$sql = "SELECT i.*, c.name, c.customer_code, c.address,
        (SELECT id FROM payments WHERE invoice_id = i.id ORDER BY id DESC LIMIT 1) as payment_id,
        (SELECT proof_file FROM payments WHERE invoice_id = i.id ORDER BY id DESC LIMIT 1) as payment_proof_path
        " . $sql_base . " ORDER BY i.generated_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Check if invoices already generated for current month
$cur_m = date('n');
$cur_y = date('Y');
$stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE period_month = ? AND period_year = ?");
$stmt_chk->execute([$cur_m, $cur_y]);
$already_generated = $stmt_chk->fetchColumn() > 0;

// Statistics Calculation
$stats_sql = "SELECT 
    COUNT(*) as total_count,
    SUM(amount) as total_amount,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN (status = 'unpaid' OR status = 'pending' OR status = 'overdue') THEN 1 ELSE 0 END) as unpaid_count,
    SUM(CASE WHEN (status = 'unpaid' OR status = 'pending' OR status = 'overdue') THEN amount ELSE 0 END) as unpaid_amount
    FROM invoices WHERE period_month = ? AND period_year = ?";
$stmt_stats = $pdo->prepare($stats_sql);
$stmt_stats->execute([$month, $year]);
$stats = $stmt_stats->fetch();
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 border-b border-gray-200 dark:border-slate-700 pb-4">
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
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Keuangan</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                 <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Keuangan</h2>
                 <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pantau tagihan dan verifikasi pembayaran.</p>
            </div>
            <div class="flex gap-2">
                 <?php if (!$already_generated): ?>
                 <button type="button" onclick="openModal('confirmGenerateModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    Generate Batch
                </button>
                <?php else: ?>
                <button type="button" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed" disabled title="Tagihan bulan ini sudah di-generate">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    Generate Batch (Done)
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Card 1: Total -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Tagihan (<?= date('M Y', mktime(0,0,0,$month,10,$year)) ?>)</p>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white"><?= $stats['total_count'] ?> <span class="text-sm text-gray-400">Inv</span></h3>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white"><?= format_rupiah($stats['total_amount'] ?? 0) ?></h3>
                </div>
            </div>
            <!-- Card 2: Lunas -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400 font-bold">Lunas (Paid)</p>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <h3 class="text-xl sm:text-2xl font-medium text-green-600 dark:text-green-400"><?= $stats['paid_count'] ?> <span class="text-sm text-green-400">Inv</span></h3>
                    <h3 class="text-lg font-semibold text-green-600 dark:text-green-400"><?= format_rupiah($stats['paid_amount'] ?? 0) ?></h3>
                </div>
            </div>
            <!-- Card 3: Belum Lunas -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-red-500 dark:text-red-400 font-bold">Belum Lunas / Pending</p>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <h3 class="text-xl sm:text-2xl font-medium text-red-600 dark:text-red-400"><?= $stats['unpaid_count'] ?> <span class="text-sm text-red-400">Inv</span></h3>
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400"><?= format_rupiah($stats['unpaid_amount'] ?? 0) ?></h3>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="sm:flex-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Cari Pelanggan</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama atau Alamat..." class="py-2.5 px-3 ps-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-base sm:text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                            <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                    </div>
                </div>
                <div class="sm:w-48">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-base sm:text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="unpaid" <?= $status == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                        <option value="overdue" <?= $status == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                 <div class="sm:w-32">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Bulan</label>
                    <select name="month" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-base sm:text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                 <div class="sm:w-32">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tahun</label>
                    <input type="number" name="year" value="<?= $year ?>" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-base sm:text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>



        <!-- Batch Form -->
        <form action="../actions/invoice_actions.php" method="POST" id="batchForm">
        <input type="hidden" name="action" value="batch_confirm">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="redirect_page" value="<?= $page ?>">
        <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($status) ?>">
        <input type="hidden" name="redirect_month" value="<?= htmlspecialchars($month) ?>">
        <input type="hidden" name="redirect_year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="redirect_search" value="<?= htmlspecialchars($search ?? '') ?>">

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden mb-20 sm:mb-0">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex justify-between items-center">
                 <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Tagihan</h2>
                 <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-white">Total: <?= format_rupiah(array_sum(array_column($invoices, 'amount'))) ?> (Page)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-[#F8FAFC] dark:bg-slate-900">
                        <tr>
                            <?php if ($_SESSION['role'] != 'Technician'): ?>
                            <th scope="col" class="px-6 py-3 text-start w-10">
                                <div class="flex items-center">
                                    <input type="checkbox" id="checkAll" class="border-gray-200 dark:border-slate-700 rounded text-blue-600 focus:ring-blue-500 dark:bg-slate-800 dark:checked:bg-blue-500">
                                </div>
                            </th>
                            <?php endif; ?>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice / Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pelanggan</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Periode</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php if (count($invoices) > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-[#F8FAFC]/50 dark:hover:bg-slate-700/50 transition-colors">
                                <?php if ($_SESSION['role'] != 'Technician'): ?>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($inv['status'] != 'paid'): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="invoice_ids[]" value="<?= $inv['id'] ?>" class="inv-check border-gray-200 dark:border-slate-700 rounded text-blue-600 focus:ring-blue-500 dark:bg-slate-800 dark:checked:bg-blue-500">
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $no++ ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="block text-sm font-semibold text-blue-600 font-mono"><?= $inv['invoice_number'] ?></span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400"><?= date('d M Y', strtotime($inv['generated_at'])) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($inv['name']) ?></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="<?= htmlspecialchars($inv['address']) ?>"><?= htmlspecialchars($inv['address'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <?= date('F Y', mktime(0, 0, 0, $inv['period_month'], 10, $inv['period_year'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white">
                                    <?= format_rupiah($inv['amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $status_badge = match($inv['status']) {
                                            'paid' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-orange-100 text-orange-800',
                                            'overdue' => 'bg-red-100 text-red-800',
                                            default => 'bg-[#F8FAFC] text-gray-800'
                                        };
                                        $status_label = match($inv['status']) {
                                            'pending' => 'Verifikasi',
                                            default => ucfirst($inv['status'])
                                        };
                                    ?>
                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium <?= $status_badge ?>">
                                        <?= $status_label ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <!-- Actions -->
                                    <?php if ($inv['status'] == 'unpaid' || $inv['status'] == 'overdue'): ?>
                                        <button type="button" 
                                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 mr-2 btn-upload transition-all"
                                            data-id="<?= $inv['id'] ?>"
                                            data-number="<?= $inv['invoice_number'] ?>"
                                            data-amount="<?= format_rupiah($inv['amount']) ?>">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                                            Konfirmasi Bayar
                                        </button>
                                    <?php elseif ($inv['status'] == 'pending' && $_SESSION['role'] != 'Technician'): ?>
                                        <button type="button" 
                                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-green-600 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 mr-2 btn-verify transition-all"
                                            data-id="<?= $inv['id'] ?>"
                                            data-payment-id="<?= $inv['payment_id'] ?>"
                                            data-number="<?= $inv['invoice_number'] ?>"
                                            data-proof="<?= $inv['payment_proof_path'] ? BASE_URL . '/uploads/' . $inv['payment_proof_path'] : '' ?>"
                                            data-manual-toggle="#hs-modal-verify">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                            Verify
                                        </button>
                                    <?php elseif ($inv['status'] == 'paid'): ?>
                                        <?php if ($_SESSION['role'] == 'Administrator' || $_SESSION['role'] == 'Finance'): ?>
                                        <!-- Rollback Form (Separate to avoid nesting) -->
                                        <!-- Note: Since we are inside a big form now, we cannot nest forms. 
                                             We must change individual buttons to use JS submission or be type="button" that triggers an external form.
                                             Simpler fix: Put batch form outside? Or use JS for batch?
                                             Let's use JS for batch submission to avoid large form nesting issues.
                                             REVERTING THE BIG FORM WRAPPER idea. 
                                             Use DIV for wrapper, and JS to collect checkbox values.
                                        -->
                                        
                                        <button type="button" onclick="confirmRollback(<?= $inv['id'] ?>, <?= $inv['payment_id'] ?>)" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-orange-600 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 mr-2 transition-all" title="Rollback Confirmation">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                            Rollback
                                        </button>
                                        <?php endif; ?>
                                        <a href="invoice_print.php?id=<?= $inv['id'] ?>" target="_blank" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                            Cetak
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="8" class="px-6 py-10 text-center">
                                    <div class="flex flex-col justify-center items-center">
                                        <div class="size-12 rounded-full bg-[#F8FAFC] dark:bg-slate-700 flex items-center justify-center mb-3">
                                            <svg class="size-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                        </div>
                                        <h3 class="text-gray-800 dark:text-gray-200 font-semibold">Tidak ada tagihan ditemukan</h3>
                                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Coba ubah filter periode atau status.</p>
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
        </form>

        <!-- Floating Batch Action Bar -->
        <div id="batchActionBar" class="fixed bottom-0 inset-x-0 pb-6 pointer-events-none opacity-0 transition-opacity duration-300">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 pointer-events-auto">
                <div class="bg-gray-900 rounded-xl shadow-lg border border-gray-800 p-4 flex justify-between items-center text-white">
                    <div class="flex items-center gap-3">
                        <span class="bg-gray-700 py-1 px-2.5 rounded text-xs font-medium"><span id="selectedCount">0</span> terpilih</span>
                        <span class="text-sm text-gray-300">Tindakan Massal:</span>
                    </div>
                    <button type="button" onclick="submitBatch()" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none shadow-lg">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Konfirmasi Bayar
                    </button>
                </div>
            </div>
        </div>

        <script>
            // Batch Selection Logic
            const batchForm = document.getElementById('batchForm');
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.inv-check');
            const actionBar = document.getElementById('batchActionBar');
            const selectedCountSpan = document.getElementById('selectedCount');

            function updateBatchUI() {
                const count = document.querySelectorAll('.inv-check:checked').length;
                selectedCountSpan.innerText = count;
                if (count > 0) {
                    actionBar.classList.remove('opacity-0');
                    actionBar.classList.add('opacity-100');
                } else {
                    actionBar.classList.add('opacity-0');
                    actionBar.classList.remove('opacity-100');
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateBatchUI();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBatchUI);
            });

            function submitBatch() {
                if (confirm('Konfirmasi pembayaran untuk ' + selectedCountSpan.innerText + ' tagihan terpilih?\n\nSemua tagihan akan ditandai LUNAS (Cash/Verified).')) {
                    batchForm.submit();
                }
            }
            
            // Rollback Helper Form Submission to avoid nested form issues
            function confirmRollback(invId, payId) {
                if (confirm('Batalkan konfirmasi pembayaran ini? Status akan kembali menjadi Pending Check.')) {
                    // Create a temporary form to submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../actions/invoice_actions.php';
                    
                    const fields = {
                        action: 'rollback_payment',
                        invoice_id: invId,
                        payment_id: payId,
                        csrf_token: '<?= generate_csrf_token() ?>',
                        redirect_page: '<?= $page ?>',
                        redirect_status: '<?= htmlspecialchars($status) ?>',
                        redirect_month: '<?= htmlspecialchars($month) ?>',
                        redirect_year: '<?= htmlspecialchars($year) ?>',
                        redirect_search: '<?= htmlspecialchars($search ?? '') ?>'
                    };

                    for (const key in fields) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
        
        <div class="text-center mt-5 text-gray-400 text-xs text-opacity-0">.</div><!-- Spacer -->
        
        <!-- Footer Spacer handled by padding -->
    </div>
</div>

<!-- Upload Modal -->
<!-- Upload Payment Modal (Consistent Style) -->
<div id="uploadPaymentModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Upload Bukti Pembayaran</h3>
            <button type="button" onclick="closeModal('uploadPaymentModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form action="../actions/invoice_actions.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_payment">
                <input type="hidden" name="invoice_id" id="upload_id">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <!-- Preserve State -->
                <input type="hidden" name="redirect_page" value="<?= $page ?>">
                <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($status) ?>">
                <input type="hidden" name="redirect_month" value="<?= htmlspecialchars($month) ?>">
                <input type="hidden" name="redirect_year" value="<?= htmlspecialchars($year) ?>">
                
                <div class="mb-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg p-3 mb-4 flex justify-between items-center">
                        <div>
                            <span class="block text-xs uppercase text-blue-500 dark:text-blue-400 font-bold">No. Tagihan</span>
                            <span id="upload_number" class="block font-mono font-bold text-gray-800 dark:text-white"></span>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs uppercase text-blue-500 dark:text-blue-400 font-bold">Total</span>
                            <span id="upload_amount" class="block font-bold text-gray-800 dark:text-white"></span>
                        </div>
                    </div>
                    
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Metode Pembayaran</label>
                    <select name="payment_method" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-base sm:text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white mb-4">
                        <option value="transfer">Bank Transfer (BCA)</option>
                        <!-- <option value="ewallet">E-Wallet (OVO/GoPay/Dana)</option> -->
                        <option value="cash">Tunai (Cash)</option>
                    </select>

                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Bukti Transfer (Opsional)</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-slate-600 border-dashed rounded-lg cursor-pointer bg-[#F1F5F9] dark:bg-slate-700 hover:bg-[#F8FAFC] dark:hover:bg-slate-600 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-4 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Klik untuk upload atau drag & drop</p>
                                <p class="text-[10px] text-gray-400 mt-1">PG, PNG, GIF (MAX. 2MB)</p>
                            </div>
                            <input id="dropzone-file" name="proof_file" type="file" class="hidden" accept="image/*" />
                        </label>
                    </div> 
                </div>

                <?php if ($_SESSION['role'] == 'Administrator' || $_SESSION['role'] == 'Finance'): ?>
                <div class="flex items-center mb-4">
                    <input id="auto_verify" name="auto_verify" type="checkbox" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 dark:bg-slate-700 dark:border-slate-600 rounded focus:ring-blue-500" checked>
                    <label for="auto_verify" class="ms-2 text-sm font-medium text-gray-800 dark:text-gray-300">Verifikasi Langsung (Tandai Lunas)</label>
                </div>
                <?php endif; ?>
                
                <div class="flex justify-end gap-x-2 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="closeModal('uploadPaymentModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                        Batal
                    </button>
                    <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        Kirim & Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verify Modal -->
<div id="hs-modal-verify" class="hs-overlay hidden w-full h-full fixed top-0 start-0 z-[60] overflow-x-hidden overflow-y-auto pointer-events-none">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Verifikasi Pembayaran</h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-white hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none" data-manual-toggle="#hs-modal-verify">
                    <span class="sr-only">Close</span>
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="mb-4 text-center bg-[#F8FAFC] dark:bg-slate-700 rounded-lg p-2">
                    <img id="verify_img" src="" alt="Bukti Transfer" class="max-h-64 mx-auto rounded shadow-sm object-contain">
                </div>
                <form action="../actions/invoice_actions.php" method="POST">
                    <input type="hidden" name="action" value="verify_payment">
                    <input type="hidden" name="invoice_id" id="verify_id">
                    <input type="hidden" name="payment_id" id="verify_payment_id">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <!-- Preserve State -->
                    <input type="hidden" name="redirect_page" value="<?= $page ?>">
                    <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($status) ?>">
                    <input type="hidden" name="redirect_month" value="<?= htmlspecialchars($month) ?>">
                    <input type="hidden" name="redirect_year" value="<?= htmlspecialchars($year) ?>">
                    <input type="hidden" name="redirect_search" value="<?= htmlspecialchars($search) ?>">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button type="submit" name="decision" value="approve" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700">
                            Terima (Approve)
                        </button>
                        <button type="submit" name="decision" value="reject" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700">
                            Tolak (Reject)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Upload Modal
        const uploadButtons = document.querySelectorAll('.btn-upload');
        uploadButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('upload_id').value = btn.dataset.id;
                document.getElementById('upload_number').textContent = btn.dataset.number;
                document.getElementById('upload_amount').textContent = btn.dataset.amount;
                openModal('uploadPaymentModal');
            });
        });

        // Verify Modal
        const verifyButtons = document.querySelectorAll('.btn-verify');
        verifyButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('verify_id').value = btn.dataset.id;
                document.getElementById('verify_payment_id').value = btn.dataset.paymentId;
                document.getElementById('verify_img').src = btn.dataset.proof;
            });
        });
    });
</script>

<!-- Confirm Generate Modal -->
<div id="confirmGenerateModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-sm w-full">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-4">
                <svg class="size-6 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Generate</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Apakah anda yakin ingin generate tagihan bulan ini untuk semua pelanggan aktif?
            </p>
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeModal('confirmGenerateModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                    Batal
                </button>
                <a href="../cron/cron_generate_invoices.php" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
                    Ya, Generate
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
