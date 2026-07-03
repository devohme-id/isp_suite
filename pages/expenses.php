<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Finance']);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['q'] ?? '';
$category_filter = $_GET['category'] ?? '';

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "description LIKE :search";
    $params[':search'] = "%$search%";
}

if (!empty($category_filter)) {
    $where_clauses[] = "category = :category";
    $params[':category'] = $category_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE $where_sql");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE $where_sql ORDER BY expense_date DESC, id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$expenses = $stmt->fetchAll();

include '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
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
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Pengeluaran</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Pengeluaran Operasional</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Catat dan kelola semua biaya operasional.</p>
        </div>
        <div>
             <button type="button" onclick="openModal('addExpenseModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-all">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Tambah Pengeluaran
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm mb-6">
        <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                     <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <!-- Preserves other filters via hidden inputs if needed, or handled by form submission -->
                <input type="text" name="q" placeholder="Cari deskripsi pengeluaran..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
            </div>
            <div class="sm:w-48">
                <select name="category" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Kategori</option>
                    <option value="Biaya Bulanan ISP" <?= $category_filter == 'Biaya Bulanan ISP' ? 'selected' : '' ?>>Biaya Bulanan ISP</option>
                    <option value="Pengadaan Hardware" <?= $category_filter == 'Pengadaan Hardware' ? 'selected' : '' ?>>Pengadaan Hardware</option>
                    <option value="Operasional" <?= $category_filter == 'Operasional' ? 'selected' : '' ?>>Operasional</option>
                    <option value="Gaji Karyawan" <?= $category_filter == 'Gaji Karyawan' ? 'selected' : '' ?>>Gaji Karyawan</option>
                    <option value="Marketing" <?= $category_filter == 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                    <option value="Lainnya" <?= $category_filter == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
            <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                Filter Data
            </button>
        </form>
    </div>

    <!-- Table Card -->
    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                    <!-- Table -->
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">No</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Keterangan</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jumlah</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bukti</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data pengeluaran.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($expenses as $row): ?>
                                    <tr class="dark:text-gray-400">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= $no++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200"><?= date('d M Y', strtotime($row['expense_date'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                            <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-white">
                                                <?= htmlspecialchars($row['category']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200 max-w-xs truncate"><?= htmlspecialchars($row['description']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800 dark:text-gray-200"><?= format_rupiah($row['amount']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                            <?php if ($row['proof_file']): ?>
                                                <a href="<?= BASE_URL ?>/uploads/<?= $row['proof_file'] ?>" target="_blank" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none transition-all">
                                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                    Lihat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                            <div class="flex justify-end gap-x-2">
                                        <!-- Edit Button handled by JS script below -->
                                        <button type="button" 
                                            class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2 btn-edit"
                                            data-id="<?= $row['id'] ?>"
                                            data-date="<?= $row['expense_date'] ?>"
                                            data-category="<?= htmlspecialchars($row['category']) ?>"
                                            data-description="<?= htmlspecialchars($row['description']) ?>"
                                            data-amount="<?= $row['amount'] ?>">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                            Edit
                                        </button>
                                                <button type="button" 
                                                        data-manual-toggle="#hs-delete-expense-modal"
                                                        onclick="setDeleteId(<?= $row['id'] ?>)"
                                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none transition-all">
                                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-slate-700">
                        <div>
                             <p class="text-sm text-gray-600 dark:text-gray-400">
                                Menampilkan <span class="font-semibold text-gray-800 dark:text-white"><?= $total_rows > 0 ? $offset + 1 : 0 ?></span> - <span class="font-semibold text-gray-800 dark:text-white"><?= min($offset + $limit, $total_rows) ?></span> dari <span class="font-semibold text-gray-800 dark:text-white"><?= $total_rows ?></span> data
                             </p>
                        </div>
                        <div>
                            <?= render_pagination($total_pages, $page) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Tambah Pengeluaran Baru</h3>
            <button type="button" onclick="closeModal('addExpenseModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/expense_actions.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- Date -->
                <div>
                  <label for="expense_date" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Tanggal</label>
                  <input type="date" id="expense_date" name="expense_date" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]" required value="<?= date('Y-m-d') ?>">
                </div>

                <!-- Amount -->
                <div>
                   <label for="amount" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Jumlah (Rp)</label>
                   <input type="text" id="amount" name="amount" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="0" required onkeyup="formatCurrency(this)">
                </div>
            </div>

            <div class="mb-5">
                <!-- Category -->
                <label for="category" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Kategori</label>
                <input type="text" id="category" name="category" placeholder="Contoh: Biaya ISP, Hardware, Gaji" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required list="category_list">
                <datalist id="category_list">
                      <option value="Biaya Bulanan ISP">
                      <option value="Pengadaan Hardware">
                      <option value="Operasional">
                      <option value="Gaji Karyawan">
                      <option value="Marketing">
                      <option value="Lainnya">
                </datalist>
            </div>

            <div class="mb-5">
                <!-- Description -->
                <label for="description" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Keterangan Detail</label>
                <textarea id="description" name="description" rows="3" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Deskripsi lengkap pengeluaran..."></textarea>
            </div>

            <div class="mb-5">
                <!-- Proof File -->
                <label for="proof_file" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Bukti / Nota (Opsional)</label>
                <input type="file" name="proof_file" id="proof_file" class="block w-full border border-gray-200 dark:border-slate-700 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 file:bg-[#F8FAFC] dark:file:bg-slate-700 dark:file:text-gray-300 file:border-0 file:me-4 file:py-3 file:px-4">
            </div>

            <div class="flex justify-end items-center gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('addExpenseModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                  Batal
                </button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                  Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Expense Modal -->
<div id="editExpenseModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Pengeluaran</h3>
            <button type="button" onclick="closeModal('editExpenseModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/expense_actions.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="expense_id" id="edit_expense_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- Date -->
                <div>
                  <label for="edit_expense_date" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Tanggal</label>
                  <input type="date" id="edit_expense_date" name="expense_date" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:[color-scheme:dark]" required>
                </div>

                <!-- Amount -->
                <div>
                   <label for="edit_amount" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Jumlah (Rp)</label>
                   <input type="text" id="edit_amount" name="amount" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required onkeyup="formatCurrency(this)">
                </div>
            </div>

            <div class="mb-5">
                <!-- Category -->
                <label for="edit_category" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Kategori</label>
                <input type="text" id="edit_category" name="category" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required list="category_list">
            </div>

            <div class="mb-5">
                <!-- Description -->
                <label for="edit_description" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Keterangan Detail</label>
                <textarea id="edit_description" name="description" rows="3" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div class="mb-5">
                <!-- Proof File -->
                <label for="edit_proof_file" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Update Bukti (Opsional)</label>
                <input type="file" name="proof_file" id="edit_proof_file" class="block w-full border border-gray-200 dark:border-slate-700 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 file:bg-[#F8FAFC] dark:file:bg-slate-700 dark:file:text-gray-300 file:border-0 file:me-4 file:py-3 file:px-4">
                <p class="text-xs text-gray-500 mt-2">Biarkan kosong jika tidak ingin mengubah bukti yang sudah ada.</p>
            </div>
            
            <div class="flex justify-end items-center gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('editExpenseModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                  Batal
                </button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                  Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="hs-delete-expense-modal" class="hs-overlay hidden w-full h-full fixed top-0 start-0 z-[60] overflow-x-hidden overflow-y-auto pointer-events-none">
  <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
    <div class="w-full flex flex-col bg-white dark:bg-slate-800 border dark:border-slate-700 shadow-sm rounded-xl pointer-events-auto">
      <div class="p-4 overflow-y-auto text-center">
          <div class="flex justify-center items-center size-20 rounded-full bg-red-100 mx-auto mb-4">
            <svg class="size-10 text-red-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="18" y1="9" x2="12" y2="15"/><line x1="12" y1="9" x2="18" y2="15"/></svg>
          </div>
          <h3 class="mb-2 text-2xl font-bold text-gray-800 dark:text-white">Hapus Pengeluaran?</h3>
          <p class="text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan.</p>
      </div>

      <form action="../actions/expense_actions.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="expense_id" id="delete_expense_id">
        
        <div class="flex justify-center items-center gap-x-2 py-3 px-4 border-t dark:border-slate-700">
            <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none" data-manual-toggle="#hs-delete-expense-modal">
              Batal
            </button>
            <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:pointer-events-none">
              Hapus
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function formatCurrency(input) {
        // remove all non-numeric chars except for null/empty
        let value = input.value.replace(/\D/g, "");
        // format with dots
        input.value = new Intl.NumberFormat('id-ID').format(value);
    }

    // The original openEditModal and setDeleteId functions are kept
    // as the HTML still references them via onclick attributes.
    // The new DOMContentLoaded listener is added for a different approach
    // if buttons are updated to use data-attributes and 'btn-edit' class.

    function openEditModal(data) {
        document.getElementById('edit_expense_id').value = data.id;
        document.getElementById('edit_expense_date').value = data.expense_date;
        document.getElementById('edit_category').value = data.category;
        document.getElementById('edit_description').value = data.description;
        document.getElementById('edit_amount').value = new Intl.NumberFormat('id-ID').format(data.amount);

        if (data.proof_file) {
            let proofLink = document.createElement('a');
            proofLink.href = '../uploads/' + data.proof_file;
            proofLink.target = '_blank';
            proofLink.className = 'text-blue-600 underline text-xs block mt-1';
            proofLink.innerText = 'Lihat Bukti Saat Ini: ' + data.proof_file;
            
            // Check if element exists to avoid duplication or error
            let existingProof = document.getElementById('current_proof_link');
            if (existingProof) existingProof.remove();
            
            proofLink.id = 'current_proof_link';
            document.getElementById('edit_proof_file').parentNode.appendChild(proofLink);
        } else {
             let existingProof = document.getElementById('current_proof_link');
             if (existingProof) existingProof.remove();
        }

        openModal('editExpenseModal');
    }

function setDeleteId(id) {
    document.getElementById('delete_expense_id').value = id;
}
</script>

<?php include '../includes/footer.php'; ?>
