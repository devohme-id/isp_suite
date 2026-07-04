<?php
// Fetch OLTs with active port counts
$search = clean_input($_GET['search'] ?? '');
$sql_base = "SELECT o.*, 
            (SELECT COUNT(*) FROM olt_ports p WHERE p.olt_id = o.id AND p.status = 'active') as active_ports 
            FROM olts o WHERE 1=1";
$params = [];

if ($search) {
    $sql_base .= " AND (o.olt_name LIKE ? OR o.olt_model LIKE ? OR o.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql_base .= " ORDER BY o.olt_name ASC";

try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $olts = $stmt->fetchAll();
} catch (PDOException $e) {
    $olts = [];
}
?>

<!-- Header Toolbar -->
<div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen OLT (Optical Line Terminal)</h2>
        <p class="text-xs text-gray-600 dark:text-gray-400">Kelola perangkat OLT pusat, port jaringan, dan monitoring status port.</p>
    </div>
    <button onclick="openOltModal('add')" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-transparent font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-all text-sm shadow-sm">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Tambah OLT Baru
    </button>
</div>

<!-- Flash Message -->
<?php $flash = get_flash_message(); if ($flash): ?>
    <div class="mb-4 p-4 rounded-lg text-sm <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Filter & Search Toolbar -->
<div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="hidden" name="tab" value="olt">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                 <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input type="text" name="search" placeholder="Cari nama OLT, model, IP atau lokasi..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
        </div>
        <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
            Cari Data
        </button>
    </form>
</div>

<!-- OLT Table -->
<div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">OLT Name / Model</th>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">IP & Location</th>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Port Utilization</th>
                    <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                <?php if (empty($olts)): ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada perangkat OLT terdaftar.</td></tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php foreach ($olts as $olt): ?>
                        <?php 
                            $usage_percent = ($olt['total_ports'] > 0) ? ($olt['active_ports'] / $olt['total_ports']) * 100 : 0;
                            $color_class = $usage_percent >= 80 ? 'bg-red-500' : ($usage_percent >= 50 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                <?= $no++ ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-x-3">
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center justify-center size-9 rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 font-bold">
                                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/><line x1="10" x2="10.01" y1="6" y2="6"/><line x1="10" x2="10.01" y1="18" y2="18"/></svg>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($olt['olt_name']) ?></span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($olt['olt_model'] ?: '-') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                <div class="font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($olt['ip_address'] ?: '-') ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="<?= htmlspecialchars($olt['location']) ?>"><?= htmlspecialchars($olt['location'] ?: '-') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-x-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400 w-12 font-medium"><?= $olt['active_ports'] ?>/<?= $olt['total_ports'] ?></span>
                                    <div class="flex w-full h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700 max-w-[100px]">
                                        <div class="flex flex-col justify-center overflow-hidden <?= $color_class ?>" role="progressbar" style="width: <?= $usage_percent ?>%" aria-valuenow="<?= $usage_percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end">
                                <a href="olt_detail.php?id=<?= $olt['id'] ?>" class="py-2 px-2.5 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-500 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2 transition-all">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Ports
                                </a>

                                <button onclick="editOlt(<?= htmlspecialchars(json_encode($olt)) ?>)" class="py-2 px-2.5 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2 transition-all">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                    Edit
                                </button>
                                <button onclick="confirmDelete(<?= $olt['id'] ?>, '<?= htmlspecialchars($olt['olt_name']) ?>')" class="py-2 px-2.5 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-all">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- OLT Modal -->
<div id="oltModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
    <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="modalTitle" class="font-bold text-gray-800 dark:text-white">Tambah OLT Baru</h3>
            <button type="button" onclick="closeModal('oltModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/olt_actions.php" method="POST" class="p-6 overflow-y-auto">
            <input type="hidden" name="action" id="formAction" value="create_olt">
            <input type="hidden" name="id" id="oltId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama OLT</label>
                    <input type="text" name="olt_name" id="oltName" required placeholder="e.g. OLT-PUSAT-01" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Model / Brand</label>
                    <input type="text" name="olt_model" id="oltModel" placeholder="e.g. Huawei MA5608T" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">IP Address</label>
                    <input type="text" name="ip_address" id="ipAddress" placeholder="e.g. 10.10.20.2" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div id="totalPortsContainer">
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Jumlah Port GPON</label>
                    <input type="number" name="total_ports" id="totalPorts" value="8" min="1" max="128" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Lokasi / Detail Penempatan</label>
                <input type="text" name="location" id="location" placeholder="e.g. Rack 1, Ruang Server Lt. 2" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Latitude</label>
                    <input type="text" name="latitude" id="latitude" placeholder="-6.2000" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Longitude</label>
                    <input type="text" name="longitude" id="longitude" placeholder="106.8166" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Catatan Tambahan</label>
                <textarea name="notes" id="notes" rows="3" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Catatan teknikal perangkat..."></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('oltModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">Batal</button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div id="confirmDeleteModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-sm w-full">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                <svg class="size-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Hapus OLT</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Apakah Anda yakin ingin menghapus perangkat OLT <strong id="delete_name" class="text-gray-800 dark:text-white"></strong>? Penghapusan ini akan menghapus semua port default pada OLT tersebut. Data tidak dapat dikembalikan.
            </p>
            <form action="../actions/olt_actions.php" method="POST">
                <input type="hidden" name="action" value="delete_olt">
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

<script>
    function openOltModal(mode, data = null) {
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah OLT Baru';
            document.getElementById('formAction').value = 'create_olt';
            document.getElementById('oltId').value = '';
            document.getElementById('oltName').value = '';
            document.getElementById('oltModel').value = '';
            document.getElementById('ipAddress').value = '';
            document.getElementById('totalPorts').value = '8';
            document.getElementById('totalPortsContainer').style.display = 'block';
            document.getElementById('location').value = '';
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            document.getElementById('notes').value = '';
        }
        openModal('oltModal');
    }

    function editOlt(data) {
        document.getElementById('modalTitle').innerText = 'Edit OLT';
        document.getElementById('formAction').value = 'update_olt';
        document.getElementById('oltId').value = data.id;
        document.getElementById('oltName').value = data.olt_name;
        document.getElementById('oltModel').value = data.olt_model || '';
        document.getElementById('ipAddress').value = data.ip_address || '';
        document.getElementById('totalPortsContainer').style.display = 'none'; // Cannot change total ports after creation
        document.getElementById('location').value = data.location || '';
        document.getElementById('latitude').value = data.latitude || '';
        document.getElementById('longitude').value = data.longitude || '';
        document.getElementById('notes').value = data.notes || '';
        openModal('oltModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_name').innerText = name || 'OLT ini';
        openModal('confirmDeleteModal');
    }
</script>
