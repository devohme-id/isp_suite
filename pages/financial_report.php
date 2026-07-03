<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Finance']);

// Default Date Range: Current Month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// --- Backend Logic ---

// 1. Total Income (Verified Payments)
$stmt = $pdo->prepare("
    SELECT SUM(amount_paid) 
    FROM payments 
    WHERE status = 'verified' 
    AND DATE(payment_date) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_income = $stmt->fetchColumn() ?: 0;

// 2. Total Expenses
$stmt = $pdo->prepare("
    SELECT SUM(amount) 
    FROM expenses 
    WHERE DATE(expense_date) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_expense = $stmt->fetchColumn() ?: 0;

// 3. Net Balance
$net_balance = $total_income - $total_expense;

// 4. Income Breakdown (by Payment Method)
$stmt = $pdo->prepare("
    SELECT payment_method, SUM(amount_paid) as total 
    FROM payments 
    WHERE status = 'verified' 
    AND DATE(payment_date) BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total DESC
");
$stmt->execute([$start_date, $end_date]);
$income_breakdown = $stmt->fetchAll();

// 5. Expense Breakdown (by Category)
$stmt = $pdo->prepare("
    SELECT category, SUM(amount) as total 
    FROM expenses 
    WHERE DATE(expense_date) BETWEEN ? AND ?
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute([$start_date, $end_date]);
$expense_breakdown = $stmt->fetchAll();

// 6. Chart Data (Daily Trend)
// Generate all dates in range
$period = new DatePeriod(
     new DateTime($start_date),
     new DateInterval('P1D'),
     (new DateTime($end_date))->modify('+1 day')
);

$chart_labels = [];
$chart_income = [];
$chart_expense = [];

// Pre-fetch daily sums for efficiency
$stmt = $pdo->prepare("
    SELECT DATE(payment_date) as date, SUM(amount_paid) as total 
    FROM payments 
    WHERE status = 'verified' AND DATE(payment_date) BETWEEN ? AND ?
    GROUP BY DATE(payment_date)
");
$stmt->execute([$start_date, $end_date]);
$daily_income = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("
    SELECT DATE(expense_date) as date, SUM(amount) as total 
    FROM expenses 
    WHERE DATE(expense_date) BETWEEN ? AND ?
    GROUP BY DATE(expense_date)
");
$stmt->execute([$start_date, $end_date]);
$daily_expense = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($period as $dt) {
    $date_str = $dt->format('Y-m-d');
    $chart_labels[] = $dt->format('d M');
    $chart_income[] = $daily_income[$date_str] ?? 0;
    $chart_expense[] = $daily_expense[$date_str] ?? 0;
}

include '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
        <!-- Header & Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Laporan Keuangan</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Ringkasan pemasukan, pengeluaran, dan laba bersih.</p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    Cetak PDF
                </button>
                <a href="financial_export.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 transition-all">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-4 no-print">
            <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="start_date" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]">
                </div>
                <div>
                    <button type="submit" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                        Tampilkan Laporan
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid sm:grid-cols-3 gap-4 sm:gap-6">
            <!-- Income -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Pemasukan</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white">
                        <?= format_rupiah($total_income) ?>
                    </h3>
                </div>
            </div>
            <!-- Expense -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium text-red-600 dark:text-red-400">
                        <?= format_rupiah($total_expense) ?>
                    </h3>
                </div>
            </div>
            <!-- Balance -->
            <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
                <div class="flex items-center gap-x-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Laba Bersih (Balance)</p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <h3 class="text-xl sm:text-2xl font-medium <?= $net_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= format_rupiah($net_balance) ?>
                    </h3>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-4 md:p-5">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Grafik Arus Kas</h3>
            <div id="financialChart" class="h-80"></div>
        </div>

        <!-- Detailed Report (P&L Style) -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Income Table -->
            <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900">
                    <h3 class="font-bold text-gray-800 dark:text-white">Rincian Pemasukan</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Metode Pembayaran</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php if(empty($income_breakdown)): ?>
                            <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data pemasukan.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach($income_breakdown as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= $no++ ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= htmlspecialchars($row['payment_method']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium text-gray-800 dark:text-white"><?= format_rupiah($row['total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white">Total</td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-bold text-gray-800 dark:text-white"><?= format_rupiah($total_income) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Expense Table -->
            <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900">
                    <h3 class="font-bold text-gray-800 dark:text-white">Rincian Pengeluaran</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                         <?php if(empty($expense_breakdown)): ?>
                            <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data pengeluaran.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach($expense_breakdown as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= $no++ ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= htmlspecialchars($row['category']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium text-red-600 dark:text-red-400"><?= format_rupiah($row['total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                     <tfoot class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white">Total</td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-bold text-red-600 dark:text-red-400"><?= format_rupiah($total_expense) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .lg\:pl-64 { padding-left: 0 !important; }
    .no-print { display: none !important; }
    body { background-color: white !important; }
    .shadow-sm { shadow: none !important; border: 1px solid #ddd; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var options = {
        series: [{
            name: 'Pemasukan',
            data: <?= json_encode($chart_income) ?>
        }, {
            name: 'Pengeluaran',
            data: <?= json_encode($chart_expense) ?>
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: false }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth' },
        xaxis: {
            categories: <?= json_encode($chart_labels) ?>,
            tooltip: { enabled: false },
            labels: {
                style: {
                    colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151'
                },
                formatter: function (value) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
                }
            }
        },
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: false },
            foreColor: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151'
        },
        colors: ['#2563eb', '#dc2626'],
        tooltip: {
                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.9,
                    stops: [0, 90, 100]
                }
            },
    };

    var chart = new ApexCharts(document.querySelector("#financialChart"), options);
    chart.render();
});
</script>

<?php include '../includes/footer.php'; ?>
