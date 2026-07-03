<?php
require_once '../config.php';
require_login();
$page_title = 'Intelligent Dashboard';
include '../includes/header.php';
require_once '../includes/sidebar.php';

// --- DATA AGGREGATION ---

// 1. FINANCIALS (Current Month)
$current_month = date('m');
$current_year = date('Y');
$last_month = date('m', strtotime('-1 month'));
$last_month_year = date('Y', strtotime('-1 month'));

// Income (Verified Payments)
$stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM payments WHERE status = 'verified' AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
$stmt->execute([$current_month, $current_year]);
$income_this_month = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM payments WHERE status = 'verified' AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
$stmt->execute([$last_month, $last_month_year]);
$income_last_month = $stmt->fetchColumn() ?: 0;

// Expenses
$stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
$stmt->execute([$current_month, $current_year]);
$expense_this_month = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
$stmt->execute([$last_month, $last_month_year]);
$expense_last_month = $stmt->fetchColumn() ?: 0;

// Net Profit
$profit_this_month = $income_this_month - $expense_this_month;
$profit_last_month = $income_last_month - $expense_last_month;

// Growth Calculations
$income_growth = $income_last_month > 0 ? (($income_this_month - $income_last_month) / $income_last_month) * 100 : 100;
$expense_growth = $expense_last_month > 0 ? (($expense_this_month - $expense_last_month) / $expense_last_month) * 100 : 100;
$profit_growth = $profit_last_month > 0 ? (($profit_this_month - $profit_last_month) / abs($profit_last_month)) * 100 : 0;

// Filter Defaults
$filter_year = $_GET['filter_year'] ?? date('Y');

// 2. OPERATIONS & GROWTH STATS (Yearly Trend)
$chart_new = [];
$chart_churn = [];
$chart_active = [];

// Total Active Customers (Current Snapshot for Cards)
$stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'");
$total_customers = $stmt->fetchColumn();

for ($m = 1; $m <= 12; $m++) {
    // Month Start/End Dates
    $m_start = "$filter_year-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-01";
    $m_end = date('Y-m-t 23:59:59', strtotime($m_start));

    // 1. New Installations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE installation_date BETWEEN ? AND ?");
    $stmt->execute([$m_start, $m_end]);
    $chart_new[] = (int)$stmt->fetchColumn();

    // 2. Churned (Terminated in this month)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE status = 'terminated' AND updated_at BETWEEN ? AND ?");
    $stmt->execute([$m_start, $m_end]);
    $chart_churn[] = (int)$stmt->fetchColumn();

    // 3. Active (Snapshot at end of month)
    // Active = Installed <= MonthEnd AND (Currently Active OR Terminated > MonthEnd)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE installation_date <= ? AND (status = 'active' OR (status = 'terminated' AND updated_at > ?))");
    $stmt->execute([$m_end, $m_end]);
    $chart_active[] = (int)$stmt->fetchColumn();
}

// New Installations (This Month - for Financial Cards)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE MONTH(installation_date) = ? AND YEAR(installation_date) = ?");
$stmt->execute([$current_month, $current_year]);
$new_installs = $stmt->fetchColumn();

// 3. ACTION ITEMS
// Pending Payments (Need Verification)
$stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
$pending_verifications = $stmt->fetchColumn();

// Unpaid Invoices Total
$stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'unpaid' OR status = 'overdue'");
$unpaid_invoice_count = $stmt->fetchColumn();

// 4. CHARTS
// Package Distribution
$stmt = $pdo->query("SELECT p.package_name, COUNT(c.id) as total FROM internet_packages p LEFT JOIN customers c ON p.id = c.package_id GROUP BY p.id");
$pkg_dist = $stmt->fetchAll();
$pkg_labels = [];
$pkg_data = [];
foreach($pkg_dist as $p) {
    $pkg_labels[] = $p['package_name'];
    $pkg_data[] = (int)$p['total'];
}

// 6-Month Cash Flow Trend
$trend_labels = [];
$trend_income = [];
$trend_expense = [];

for ($i = 5; $i >= 0; $i--) {
    $m = date('m', strtotime("-$i months"));
    $y = date('Y', strtotime("-$i months"));
    $name = date('M', strtotime("-$i months"));
    
    $trend_labels[] = $name;
    
    // Income
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM payments WHERE status = 'verified' AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
    $stmt->execute([$m, $y]);
    $trend_income[] = (int)($stmt->fetchColumn() ?: 0);
    
    // Expense
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
    $stmt->execute([$m, $y]);
    $trend_expense[] = (int)($stmt->fetchColumn() ?: 0);
}

// 5. RECENT ACTIVITY
$stmt = $pdo->query("
    SELECT p.*, c.name as customer_name, i.invoice_number 
    FROM payments p 
    JOIN invoices i ON p.invoice_id = i.id 
    JOIN customers c ON i.customer_id = c.id 
    ORDER BY p.payment_date DESC LIMIT 5
");
$recent_payments = $stmt->fetchAll();

?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen transition-all duration-300 ease-in-out">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
        <!-- Welcome Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Overview</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?>! Ini ringkasan operasional hari ini.</p>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 font-medium bg-white dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm">
                <?= date('d F Y') ?>
            </div>
        </div>

        <!-- 1. Financial Health (KPI Cards) -->
        <div class="grid sm:grid-cols-3 gap-4 sm:gap-6">
            <!-- Income Card -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wide">Pemasukan (Bulan Ini)</h3>
                    <span class="inline-flex items-center gap-x-1 py-0.5 px-2 rounded-md text-xs font-medium <?= $income_growth >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $income_growth >= 0 ? '+' : '' ?><?= number_format($income_growth, 1) ?>%
                    </span>
                </div>
                <div class="flex items-center gap-x-4">
                    <div class="shrink-0 flex justify-center items-center size-12 bg-blue-100 text-blue-600 rounded-full">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= format_rupiah($income_this_month) ?></h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">vs <?= format_rupiah($income_last_month) ?> (Bulan Lalu)</p>
                    </div>
                </div>
            </div>

            <!-- Expense Card -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                 <div class="flex justify-between items-center mb-4">
                    <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wide">Pengeluaran (Bulan Ini)</h3>
                    <span class="inline-flex items-center gap-x-1 py-0.5 px-2 rounded-md text-xs font-medium <?= $expense_growth <= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $expense_growth >= 0 ? '+' : '' ?><?= number_format($expense_growth, 1) ?>%
                    </span>
                </div>
                 <div class="flex items-center gap-x-4">
                    <div class="shrink-0 flex justify-center items-center size-12 bg-red-100 text-red-600 rounded-full">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                     <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= format_rupiah($expense_this_month) ?></h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">vs <?= format_rupiah($expense_last_month) ?> (Bulan Lalu)</p>
                    </div>
                </div>
            </div>

             <!-- Profit Card -->
             <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                 <div class="flex justify-between items-center mb-4">
                    <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wide">Laba Bersih (Net Profit)</h3>
                </div>
                 <div class="flex items-center gap-x-4">
                    <div class="shrink-0 flex justify-center items-center size-12 bg-green-100 text-green-600 rounded-full">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                     <div>
                        <h2 class="text-2xl font-bold <?= $profit_this_month >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= format_rupiah($profit_this_month) ?></h2>
                         <p class="text-xs text-gray-500 dark:text-gray-400">Margin: <?= $income_this_month > 0 ? number_format(($profit_this_month / $income_this_month) * 100, 1) : 0 ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operations Section (Split View) -->
        <div class="grid lg:grid-cols-4 gap-6 mb-6">
            <!-- Left: Growth Chart -->
            <div class="lg:col-span-3 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                 <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wide mb-2 sm:mb-0">Pertumbuhan Pelanggan</h3>
                    <form action="" method="GET" class="flex items-center gap-2">
                        <select name="filter_year" onchange="this.form.submit()" class="py-2 px-3 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-300">
                            <?php 
                            $curr_y = date('Y');
                            // Range: 2 years back to 1 year forward
                            for($y = $curr_y - 2; $y <= $curr_y + 1; $y++): 
                            ?>
                                <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
                 <div id="customerGrowthChart" class="h-[350px]"></div>
            </div>

            <!-- Right: Operational Cards (Vertical Stack) -->
            <div class="lg:col-span-1 flex flex-col gap-4">
                <!-- Active Customers -->
                <a href="customers.php" class="group flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 hover:border-blue-500 transition-colors h-full justify-center">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 flex justify-center items-center size-10 bg-indigo-100 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Pelanggan</p>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?= number_format($total_customers) ?></h3>
                        </div>
                    </div>
                </a>

                <!-- New Installs -->
                <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 h-full justify-center">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 flex justify-center items-center size-10 bg-teal-100 text-teal-600 rounded-lg">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        </div>
                        <div>
                             <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Pemasangan Baru</p>
                             <h3 class="text-xl font-bold text-gray-800 dark:text-white">+<?= number_format($new_installs) ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Pending Verifications -->
                <a href="invoices.php" class="group flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 <?= $pending_verifications > 0 ? 'border-orange-300 ring-2 ring-orange-100' : '' ?> hover:border-orange-500 transition-colors h-full justify-center">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 flex justify-center items-center size-10 bg-orange-100 text-orange-600 rounded-lg group-hover:bg-orange-600 group-hover:text-white transition-colors">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <div>
                             <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Butuh Verifikasi</p>
                             <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?= number_format($pending_verifications) ?></h3>
                        </div>
                    </div>
                </a>

                <!-- Unpaid Invoices -->
                <a href="invoices.php?status=unpaid" class="group flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 hover:border-red-500 transition-colors h-full justify-center">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 flex justify-center items-center size-10 bg-red-100 text-red-600 rounded-lg group-hover:bg-red-600 group-hover:text-white transition-colors">
                             <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                        </div>
                        <div>
                             <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Belum Lunas</p>
                             <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?= number_format($unpaid_invoice_count) ?></h3>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- 3. Charts Section -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Cash Flow Chart -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Arus Kas (6 Bulan Terakhir)</h3>
                <div id="cashFlowChart" class="h-80"></div>
            </div>

            <!-- Package Distribution -->
            <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Sebaran Paket</h3>
                <div id="packageChart" class="h-80 flex justify-center items-center"></div>
            </div>
        </div>

        <!-- 4. Recent Activity (Payments) -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 dark:text-white">Pembayaran Terbaru</h3>
                <a href="invoices.php" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Pelanggan</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Invoice</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Metode</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-gray-800 dark:text-gray-200">
                        <?php if(empty($recent_payments)): ?>
                            <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pembayaran terbaru.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach($recent_payments as $rp): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?= $no++ ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($rp['customer_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">#<?= htmlspecialchars($rp['invoice_number']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white"><?= format_rupiah($rp['amount_paid']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($rp['payment_method']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium <?= $rp['status'] === 'verified' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' ?>">
                                        <?= ucfirst($rp['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Helper to check dark mode
const isDark = document.documentElement.classList.contains('dark');
const textColor = isDark ? '#9ca3af' : '#374151';

// Cash Flow Chart
var cpfOptions = {
    series: [{
        name: 'Pemasukan',
        data: <?= json_encode($trend_income) ?>
    }, {
        name: 'Pengeluaran',
        data: <?= json_encode($trend_expense) ?>
    }],
    chart: {
        type: 'bar',
        height: 320,
        toolbar: { show: false },
        foreColor: textColor
    },
    colors: ['#2563eb', '#dc2626'],
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            borderRadius: 4
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: {
        categories: <?= json_encode($trend_labels) ?>,
        labels: { style: { colors: textColor } }
    },
    yaxis: {
        labels: {
            style: { colors: textColor },
            formatter: function (value) {
                 return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
            }
        }
    },
    fill: { opacity: 1 },
    grid: {
        borderColor: isDark ? '#374151' : '#e5e7eb',
    },
    tooltip: {
        theme: isDark ? 'dark' : 'light',
        y: {
            formatter: function (val) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
            }
        }
    }
};
var cpfChart = new ApexCharts(document.querySelector("#cashFlowChart"), cpfOptions);
cpfChart.render();

// Package Distribution Chart
var pkgOptions = {
    series: <?= json_encode($pkg_data) ?>,
    chart: {
        width: 380,
        type: 'donut',
        foreColor: textColor
    },
    labels: <?= json_encode($pkg_labels) ?>,
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    name: { color: textColor },
                    value: { color: textColor }
                }
            }
        }
    },
    stroke: {
        show: true,
        colors: isDark ? ['#1e293b'] : ['#ffffff'] 
    },
    legend: { 
        position: 'bottom',
        labels: { colors: textColor }
    },
    tooltip: { theme: isDark ? 'dark' : 'light' },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: { width: 300 },
            legend: { position: 'bottom' }
        }
    }]
};
var pkgChart = new ApexCharts(document.querySelector("#packageChart"), pkgOptions);
pkgChart.render();

// Customer Growth Chart (Yearly Trend)
var growthOptions = {
    series: [
        { name: 'Penambahan', data: <?= json_encode($chart_new) ?> },
        { name: 'Pelanggan Berhenti', data: <?= json_encode($chart_churn) ?> },
        { name: 'Aktual', data: <?= json_encode($chart_active) ?> }
    ],
    chart: {
        height: 350,
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false },
        foreColor: textColor
    },
    colors: ['#10b981', '#ef4444', '#8b5cf6'], // Green, Red, Purple
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    markers: {
        size: 5,
        hover: { size: 7 }
    },
    grid: {
        borderColor: isDark ? '#374151' : '#e5e7eb',
        strokeDashArray: 4
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: textColor } }
    },
    yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
            style: { colors: textColor },
            formatter: function (val) {
                return val.toFixed(0);
            }
        }
    },
    legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        labels: { colors: textColor }
    },
    tooltip: {
        theme: isDark ? 'dark' : 'light',
        y: {
            formatter: function (val) {
                return val + " Pelanggan"
            }
        }
    }
};
var growthChart = new ApexCharts(document.querySelector("#customerGrowthChart"), growthOptions);
growthChart.render();
</script>

<?php require_once '../includes/footer.php'; ?>
