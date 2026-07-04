<?php
// Fetch all RKs for cascading dropdown
$rks = $pdo->query("SELECT id, rk_name FROM rk_points ORDER BY rk_name ASC")->fetchAll();

// Fetch DPs with capacity usage and feed cores
$search = clean_input($_GET['search'] ?? '');
$sql_base = "SELECT dp.*, 
            (SELECT COUNT(*) FROM customers c WHERE c.dp_id = dp.id) as used_ports,
            dc.tube_number, dc.core_number, d.dist_code
            FROM drop_points dp
            LEFT JOIN distribution_cores dc ON dp.dist_core_id = dc.id
            LEFT JOIN distributions d ON dc.distribution_id = d.id
            WHERE 1=1";
$params = [];

if ($search) {
    $sql_base .= " AND (dp.dp_name LIKE ? OR dp.dp_code LIKE ? OR dp.zone_area LIKE ? OR d.dist_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql_base .= " ORDER BY dp.dp_code ASC, dp.dp_name ASC";

try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $dps = $stmt->fetchAll();
} catch (PDOException $e) {
    $dps = [];
}
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Optical Drop Points (DP / ODP)</h2>
        <p class="text-xs text-gray-600 dark:text-gray-400">Kelola Drop Point distribusi pelanggan, port idle, dan upstream core feeding.</p>
    </div>
    <button onclick="openDpModal('add')" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-transparent font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-all text-sm shadow-sm">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Tambah Drop Point Baru
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
        <input type="hidden" name="tab" value="dp">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                 <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input type="text" name="search" placeholder="Cari kode/nama DP, wilayah, atau kabel..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
        </div>
        <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
            Filter Data
        </button>
    </form>
</div>

<!-- DP Table -->
<div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Code</th>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">DP Name / Info</th>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Upstream Feed Core</th>
                    <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Capacity Usage</th>
                    <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                <?php if (empty($dps)): ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada Drop Point terdaftar.</td></tr>
                <?php else: ?>
                    <?php foreach ($dps as $dp): ?>
                        <?php 
                            $usage_percent = ($dp['total_ports'] > 0) ? ($dp['used_ports'] / $dp['total_ports']) * 100 : 0;
                            $color_class = $usage_percent >= 80 ? 'bg-red-500' : ($usage_percent >= 50 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800 dark:text-white">
                                <?= htmlspecialchars($dp['dp_code']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-x-3">
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center justify-center size-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="12" x="3" y="10" rx="2"/><path d="M12 10v12"/><path d="M7 10v12"/><path d="M17 10v12"/><path d="M7 2h10"/><path d="M12 2v8"/></svg>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($dp['dp_name']) ?></span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($dp['zone_area']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400 font-medium">
                                <?= $dp['dist_code'] ? htmlspecialchars($dp['dist_code'] . ' - Tube ' . $dp['tube_number'] . ' Core ' . $dp['core_number']) : '<span class="text-red-500 font-semibold text-xs">No Core Input</span>' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-x-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400 w-12 font-medium"><?= $dp['used_ports'] ?>/<?= $dp['total_ports'] ?></span>
                                    <div class="flex w-full h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700 max-w-[100px]">
                                        <div class="flex flex-col justify-center overflow-hidden <?= $color_class ?>" role="progressbar" style="width: <?= $usage_percent ?>%" aria-valuenow="<?= $usage_percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end">
                                <a href="dp_detail.php?id=<?= $dp['id'] ?>" class="py-2 px-2.5 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-500 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2 transition-all">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View Map
                                </a>

                                <button onclick="editDP(<?= htmlspecialchars(json_encode($dp)) ?>)" class="py-2 px-2.5 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2 transition-all">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                    Edit
                                </button>
                                <button onclick="confirmDelete(<?= $dp['id'] ?>, '<?= htmlspecialchars($dp['dp_name']) ?>')" class="py-2 px-2.5 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-all">
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

<!-- DP Modal -->
<div id="dpModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="modalTitle" class="font-bold text-gray-800 dark:text-white">Tambah Drop Point Baru</h3>
            <button onclick="closeModal('dpModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="../actions/dp_actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="dpId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Nama Drop Point (DP)</label>
                    <input type="text" name="dp_name" id="dpName" required placeholder="e.g. DP-A01-A" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Total Port Drop Point</label>
                    <input type="number" name="total_ports" id="totalPorts" required value="8" min="1" max="24" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <!-- Cascading dropdown sections for core assignment -->
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-500 mb-1">1. Pilih RK</label>
                    <select id="selRk" onchange="loadDistributions(this.value)" class="py-2 px-2.5 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-xs bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($rks as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['rk_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-500 mb-1">2. Kabel Distribusi</label>
                    <select id="selDist" onchange="loadDistCores(this.value)" class="py-2 px-2.5 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-xs bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">-- Pilih --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-500 mb-1">3. Core Distribusi</label>
                    <select name="dist_core_id" id="selCore" class="py-2 px-2.5 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-xs bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">-- Pilih Core --</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Zone / Area Coverage</label>
                <input type="text" name="zone_area" id="zoneArea" placeholder="e.g. Perumahan X Blok A" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Latitude</label>
                    <input type="text" name="latitude" id="latitude" placeholder="-6.2000" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Longitude</label>
                    <input type="text" name="longitude" id="longitude" placeholder="106.8166" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Catatan</label>
                <textarea name="notes" id="notes" rows="2" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('dpModal')" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold border rounded-lg bg-white text-gray-800 dark:bg-slate-800 dark:border-slate-700 dark:text-white">Batal</button>
                <button type="submit" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div id="confirmDeleteModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4">
    <div class="m-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-sm w-full p-6 text-center">
        <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
            <svg class="size-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Hapus Drop Point</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Apakah Anda yakin ingin menghapus Drop Point <strong id="delete_name" class="text-gray-800 dark:text-white"></strong>? Data yang dihapus tidak dapat dikembalikan.
        </p>
        <form action="../actions/dp_actions.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeModal('confirmDeleteModal')" class="py-2 px-4 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200">Batal</button>
                <button type="submit" class="py-2 px-4 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function loadDistributions(rkId, selectedDistId = '') {
        const select = document.getElementById('selDist');
        select.innerHTML = '<option value="">-- Loading --</option>';
        document.getElementById('selCore').innerHTML = '<option value="">-- Pilih Core --</option>';
        if (!rkId) {
            select.innerHTML = '<option value="">-- Pilih --</option>';
            return;
        }

        fetch(`../actions/ftth_data.php?action=get_distributions&rk_id=${rkId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '<option value="">-- Pilih --</option>';
                    data.distributions.forEach(d => {
                        html += `<option value="${d.id}" ${d.id == selectedDistId ? 'selected' : ''}>${d.dist_code} (${d.coverage_area})</option>`;
                    });
                    select.innerHTML = html;
                    if (selectedDistId) loadDistCores(selectedDistId);
                } else {
                    select.innerHTML = '<option value="">-- Gagal --</option>';
                }
            });
    }

    function loadDistCores(distId, selectedCoreId = '') {
        const select = document.getElementById('selCore');
        select.innerHTML = '<option value="">-- Loading --</option>';
        if (!distId) {
            select.innerHTML = '<option value="">-- Pilih Core --</option>';
            return;
        }

        fetch(`../actions/ftth_data.php?action=get_dist_cores&distribution_id=${distId}&filter=idle`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '<option value="">-- Pilih Core --</option>';
                    // If we're editing, let's inject current core if it was selected
                    if (selectedCoreId) {
                        html += `<option value="${selectedCoreId}" selected>Core Aktif Sekarang</option>`;
                    }
                    data.cores.forEach(c => {
                        if (c.id != selectedCoreId) {
                            html += `<option value="${c.id}">Tube ${c.tube_number} Core ${c.core_number}</option>`;
                        }
                    });
                    select.innerHTML = html;
                } else {
                    select.innerHTML = '<option value="">-- Gagal --</option>';
                }
            });
    }

    function openDpModal(mode) {
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Drop Point Baru';
            document.getElementById('formAction').value = 'create';
            document.getElementById('dpId').value = '';
            document.getElementById('dpName').value = '';
            document.getElementById('totalPorts').value = '8';
            document.getElementById('selRk').value = '';
            document.getElementById('selDist').innerHTML = '<option value="">-- Pilih --</option>';
            document.getElementById('selCore').innerHTML = '<option value="">-- Pilih Core --</option>';
            document.getElementById('zoneArea').value = '';
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            document.getElementById('notes').value = '';
        }
        openModal('dpModal');
    }

    function editDP(data) {
        document.getElementById('modalTitle').innerText = 'Edit Drop Point';
        document.getElementById('formAction').value = 'update';
        document.getElementById('dpId').value = data.id;
        document.getElementById('dpName').value = data.dp_name;
        document.getElementById('totalPorts').value = data.total_ports;
        document.getElementById('zoneArea').value = data.zone_area || '';
        document.getElementById('latitude').value = data.latitude || '';
        document.getElementById('longitude').value = data.longitude || '';
        document.getElementById('notes').value = data.notes || '';
        
        // Load cascading dropdowns back if dist_core_id is set
        if (data.dist_core_id) {
            // Find RK and Dist associated with this core
            fetch(`../actions/ftth_data.php?action=get_network_path&dp_id=${data.id}`)
                .then(res => res.json())
                .then(pathData => {
                    if (pathData.success && pathData.path) {
                        const path = pathData.path;
                        document.getElementById('selRk').value = path.rk_id || '';
                        
                        // Load distributions with callback to set selected distribution and core
                        const distSelect = document.getElementById('selDist');
                        fetch(`../actions/ftth_data.php?action=get_distributions&rk_id=${path.rk_id}`)
                            .then(res => res.json())
                            .then(dData => {
                                if (dData.success) {
                                    let html = '<option value="">-- Pilih --</option>';
                                    dData.distributions.forEach(d => {
                                        html += `<option value="${d.id}" ${d.id == path.distribution_id ? 'selected' : ''}>${d.dist_code} (${d.coverage_area})</option>`;
                                    });
                                    distSelect.innerHTML = html;
                                    
                                    // Now load cores
                                    loadDistCores(path.distribution_id, data.dist_core_id);
                                }
                            });
                    }
                });
        }
        openModal('dpModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_name').innerText = name || 'DP ini';
        openModal('confirmDeleteModal');
    }
</script>
