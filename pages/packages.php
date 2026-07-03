<?php
require_once '../config.php';
require_login();
$page_title = 'Paket Internet';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch Packages
$stmt = $pdo->query("
    SELECT ip.*, 
    (SELECT COUNT(*) FROM customers c WHERE c.package_id = ip.id) as customer_count 
    FROM internet_packages ip 
    ORDER BY price ASC
");
$packages = $stmt->fetchAll();
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
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Paket Internet</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                 <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Paket Internet</h2>
                 <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Atur produk layanan internet dan harga.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="openModal('addPackageModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-colors">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Tambah Paket
                </button>
            </div>
        </div>



        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
             <!-- Card Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Paket</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                     <thead class="bg-[#F8FAFC] dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Paket</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kecepatan</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php $no = 1; ?>
                        <?php foreach ($packages as $pkg): ?>
                        <tr class="hover:bg-[#F8FAFC]/50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= $no++ ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800 dark:text-white">
                                <?= htmlspecialchars($pkg['package_name']) ?>
                                <div class="text-xs text-gray-400 font-normal mt-0.5 truncate max-w-xs"><?= htmlspecialchars($pkg['description']) ?></div>
                                <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mt-1 inline-flex items-center gap-1">
                                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <?= $pkg['customer_count'] ?> Pelanggan
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-medium bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                    <?= $pkg['speed_mbps'] ?> Mbps
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-white font-bold"><?= format_rupiah($pkg['price']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if($pkg['is_active']): ?>
                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium bg-[#F8FAFC] text-gray-800 dark:bg-slate-700 dark:text-gray-300">Non-Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                <!-- Edit Button handled by JS script below -->
                                <button type="button" class="btn-edit py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all"
                                    data-id="<?= $pkg['id'] ?>"
                                    data-name="<?= htmlspecialchars($pkg['package_name']) ?>"
                                    data-price="<?= $pkg['price'] ?>"
                                    data-speed="<?= $pkg['speed_mbps'] ?>"
                                    data-desc="<?= htmlspecialchars($pkg['description'] ?? '') ?>"
                                    data-active="<?= $pkg['is_active'] ?>">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                    Edit
                                </button>
                                <form action="../actions/package_actions.php" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                     <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-500 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                                         <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-[#F8FAFC] dark:bg-slate-900 rounded-b-xl">
                 <p class="text-xs text-center text-gray-500 dark:text-gray-400">Menampilkan <?= count($packages) ?> Paket Internet</p>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addPackageModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Tambah Paket Baru</h3>
            <button type="button" onclick="closeModal('addPackageModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/package_actions.php" method="POST" class="p-6 overflow-y-auto">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="grid gap-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Paket</label>
                    <input type="text" name="package_name" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Contoh: Paket Home 10Mbps" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Harga (Rp)</label>
                        <input type="number" name="price" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Contoh: 150000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Speed (Mbps)</label>
                        <input type="number" name="speed_mbps" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Contoh: 10" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Deskripsi</label>
                    <textarea name="description" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" rows="3" placeholder="Deskripsi paket..."></textarea>
                </div>
                <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="closeModal('addPackageModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editPackageModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Paket</h3>
            <button type="button" onclick="closeModal('editPackageModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/package_actions.php" method="POST" id="editForm" class="p-6 overflow-y-auto">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="grid gap-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Paket</label>
                    <input type="text" name="package_name" id="edit_name" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Contoh: Paket Home 10Mbps" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Harga (Rp)</label>
                        <input type="number" name="price" id="edit_price" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Contoh: 150000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Speed (Mbps)</label>
                        <input type="number" name="speed_mbps" id="edit_speed" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Contoh: 10" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Deskripsi</label>
                    <textarea name="description" id="edit_desc" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" rows="3" placeholder="Deskripsi paket..."></textarea>
                </div>
                <!-- Status (Active/Inactive) -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="edit_status" value="1" class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800">
                    <label for="edit_status" class="text-sm text-gray-500 ms-3 dark:text-gray-400">Aktifkan Paket?</label>
                </div>
                
                <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="closeModal('editPackageModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">Update Paket</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_price').value = btn.dataset.price;
                document.getElementById('edit_speed').value = btn.dataset.speed;
                document.getElementById('edit_desc').value = btn.dataset.desc || '';
                document.getElementById('edit_status').checked = btn.dataset.active == 1;
                
                openModal('editPackageModal');
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>
