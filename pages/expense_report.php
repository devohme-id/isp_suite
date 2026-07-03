<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Finance']);

// Filter Logic
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$category_filter = $_GET['category'] ?? '';

// Build Query
$query = "SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY expense_date DESC, id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Calculate Totals
$total_amount = 0;
$category_totals = [];

foreach ($expenses as $exp) {
    $total_amount += $exp['amount'];
    if (!isset($category_totals[$exp['category']])) {
        $category_totals[$exp['category']] = 0;
    }
    $category_totals[$exp['category']] += $exp['amount'];
}

// Prepare Data for Category Summary
arsort($category_totals);

include '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Laporan Pengeluaran</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Ringkasan pengeluaran periode <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></p>
        </div>
        <div class="flex gap-x-2">
            <button onclick="window.print()" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Cetak / PDF
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-4 mb-6">
        <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label for="start_date" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Dari Tanggal</label>
                <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Sampai Tanggal</label>
                <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]">
            </div>
            <div>
                <label for="category" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Kategori</label>
                <select name="category" id="category" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Kategori</option>
                    <option value="Biaya Bulanan ISP" <?= $category_filter == 'Biaya Bulanan ISP' ? 'selected' : '' ?>>Biaya Bulanan ISP</option>
                    <option value="Pengadaan Hardware" <?= $category_filter == 'Pengadaan Hardware' ? 'selected' : '' ?>>Pengadaan Hardware</option>
                    <option value="Operasional" <?= $category_filter == 'Operasional' ? 'selected' : '' ?>>Operasional</option>
                    <option value="Gaji Karyawan" <?= $category_filter == 'Gaji Karyawan' ? 'selected' : '' ?>>Gaji Karyawan</option>
                </select>
            </div>
            <div class="flex gap-x-2">
                <button type="submit" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                    Filter
                </button>
                <a href="expense_report.php" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Expense -->
        <div class="flex flex-col bg-white dark:bg-slate-800 border dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
            <div class="flex items-center gap-x-2">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
            </div>
            <div class="mt-1 flex items-center gap-x-2">
                <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-white">
                    <?= format_rupiah($total_amount) ?>
                </h3>
            </div>
        </div>
        
        <!-- Top Categories -->
        <?php 
        $i = 0;
        foreach ($category_totals as $cat => $amount): 
            if ($i >= 3) break; // Show top 3
            $percent = ($total_amount > 0) ? ($amount / $total_amount) * 100 : 0;
            $i++;
        ?>
        <div class="flex flex-col bg-white dark:bg-slate-800 border dark:border-slate-700 shadow-sm rounded-xl p-4 md:p-5">
            <div class="flex items-center justify-between gap-x-2">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($cat) ?></p>
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400"><?= number_format($percent, 1) ?>%</span>
            </div>
            <div class="mt-1 flex items-center gap-x-2">
                <h3 class="text-lg font-medium text-gray-800 dark:text-white">
                    <?= format_rupiah($amount) ?>
                </h3>
            </div>
             <div class="flex w-full h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden mt-2">
                <div class="flex flex-col justify-center overflow-hidden bg-blue-500" role="progressbar" style="width: <?= $percent ?>%" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Detailed Table -->
    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Rincian Transaksi</h2>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">No</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Keterangan</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data untuk periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($expenses as $row): ?>
                                    <tr class="dark:text-gray-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= $no++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= date('d/m/Y', strtotime($row['expense_date'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= htmlspecialchars($row['category']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200"><?= htmlspecialchars($row['description']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-semibold text-gray-800 dark:text-white"><?= format_rupiah($row['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Footer Total -->
                                <tr class="bg-gray-50 dark:bg-slate-900 font-bold">
                                    <td colspan="4" class="px-6 py-4 text-end text-sm text-gray-800 dark:text-white">Total Periode Ini</td>
                                    <td class="px-6 py-4 text-end text-sm text-gray-800 dark:text-white"><?= format_rupiah($total_amount) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
