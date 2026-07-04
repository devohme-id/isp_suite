<?php
// Fetch RK Points
$search = clean_input($_GET['search'] ?? '');
$sql_base = "SELECT r.*,
            (SELECT COUNT(*) FROM rk_connections rc WHERE rc.rk_id = r.id) as incoming_backbones,
            (SELECT COUNT(*) FROM distributions d WHERE d.rk_id = r.id) as outgoing_distributions
            FROM rk_points r WHERE 1=1";
$params = [];

if ($search) {
    $sql_base .= " AND (r.rk_name LIKE ? OR r.location_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql_base .= " ORDER BY r.rk_name ASC";

try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $rk_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $rk_list = [];
}

// Fetch all idle backbone cores for splicing form dropdown
$stmt_idle_cores = $pdo->query("
    SELECT bc.id, bc.tube_number, bc.core_number, b.backbone_code 
    FROM backbone_cores bc
    JOIN backbones b ON bc.backbone_id = b.id
    WHERE bc.status = 'idle'
    ORDER BY b.backbone_code ASC, bc.tube_number ASC, bc.core_number ASC
");
$idle_cores = $stmt_idle_cores->fetchAll();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen Rumah Kabel (RK)</h2>
        <p class="text-xs text-gray-600 dark:text-gray-400">Kelola Rumah Kabel (RK) Distribusi, mapping input kabel backbone, dan kabel distribusi keluar.</p>
    </div>
    <button onclick="openRkModal('add')" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-transparent font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-all text-sm shadow-sm">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Tambah RK Baru
    </button>
</div>

<!-- Flash Message -->
<?php $flash = get_flash_message(); if ($flash): ?>
    <div class="mb-4 p-4 rounded-lg text-sm <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Filter Toolbar -->
<div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="hidden" name="tab" value="rk">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                 <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input type="text" name="search" placeholder="Cari nama RK atau lokasi..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
        </div>
        <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
            Filter Data
        </button>
    </form>
</div>

<!-- RK Table -->
<div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">RK Info</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Location Description</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Connections</th>
                    <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                <?php if (empty($rk_list)): ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada RK terdaftar.</td></tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php foreach ($rk_list as $rk): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= $no++ ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-800 dark:text-white">
                                <div class="flex items-center gap-x-3">
                                    <span class="inline-flex items-center justify-center size-8 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400 font-bold text-xs">
                                        RK
                                    </span>
                                    <div>
                                        <span><?= htmlspecialchars($rk['rk_name']) ?></span>
                                        <span class="block text-[10px] text-gray-400 font-normal"><?= htmlspecialchars($rk['latitude'] . ', ' . $rk['longitude']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                <?= htmlspecialchars($rk['location_description'] ?: '-') ?>
                                <div class="text-[10px] text-gray-400"><?= htmlspecialchars($rk['notes']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                <span class="block text-xs font-medium">Backbone In: <strong class="text-blue-600"><?= $rk['incoming_backbones'] ?> Core</strong></span>
                                <span class="block text-xs font-medium">Distribusi Out: <strong class="text-green-600"><?= $rk['outgoing_distributions'] ?> Kabel</strong></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end">
                                <button onclick="viewRkDetail(<?= $rk['id'] ?>, '<?= htmlspecialchars($rk['rk_name']) ?>')" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 mr-2 transition-all">
                                    Manage Link
                                </button>
                                <button onclick="editRk(<?= htmlspecialchars(json_encode($rk)) ?>)" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 mr-2">
                                    Edit
                                </button>
                                <button onclick="confirmDeleteRk(<?= $rk['id'] ?>, '<?= htmlspecialchars($rk['rk_name']) ?>')" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-gray-200 dark:border-slate-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20">
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

<!-- RK Form Modal -->
<div id="rkModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="rkModalTitle" class="font-bold text-gray-800 dark:text-white">Tambah RK Baru</h3>
            <button onclick="closeModal('rkModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="../actions/rk_actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="rkAction" value="create_rk">
            <input type="hidden" name="id" id="rkId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Nama RK (Rumah Kabel)</label>
                <input type="text" name="rk_name" id="rkName" required placeholder="e.g. RK-A" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Deskripsi Lokasi / Penempatan</label>
                <input type="text" name="location_description" id="rkLocation" placeholder="e.g. Dekat Pos Satpam Blok A" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Latitude</label>
                    <input type="text" name="latitude" id="rkLatitude" placeholder="-6.2000" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Longitude</label>
                    <input type="text" name="longitude" id="rkLongitude" placeholder="106.8166" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Catatan</label>
                <textarea name="notes" id="rkNotes" rows="3" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Info teknikal box panel RK..."></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('rkModal')" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold border rounded-lg bg-white text-gray-800 dark:bg-slate-800 dark:border-slate-700 dark:text-white">Batal</button>
                <button type="submit" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Link Detail Modal (Incoming/Outgoing) -->
<div id="rkDetailModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-2xl w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Koneksi RK: <span id="detailRkName" class="text-blue-600"></span></h3>
            <button onclick="closeModal('rkDetailModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-6">
            <!-- Add Connection Form -->
            <div class="bg-gray-50 dark:bg-slate-900 p-4 rounded-xl border border-gray-200 dark:border-slate-800">
                <h4 class="text-xs font-bold text-gray-800 dark:text-white uppercase mb-3">Sambungkan Core Backbone Baru</h4>
                <form action="../actions/rk_actions.php" method="POST" class="flex flex-col sm:flex-row gap-3 items-end">
                    <input type="hidden" name="action" value="create_connection">
                    <input type="hidden" name="rk_id" id="detailRkId">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="flex-1">
                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Core Backbone (Idle)</label>
                        <select name="backbone_core_id" required class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-white dark:bg-slate-800 dark:text-white">
                            <option value="">-- Pilih Core Backbone --</option>
                            <?php foreach ($idle_cores as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['backbone_code']) ?> - Tube <?= $c['tube_number'] ?> Core <?= $c['core_number'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Catatan Sambungan</label>
                        <input type="text" name="notes" placeholder="e.g. Splicing ke Core Distribusi A" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-white dark:bg-slate-800 dark:text-white">
                    </div>
                    <button type="submit" class="py-2 px-4 inline-flex justify-center items-center text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">Sambungkan</button>
                </form>
            </div>

            <!-- Connection List -->
            <div>
                <h4 class="text-xs font-bold text-gray-800 dark:text-white uppercase mb-3">Daftar Core Backbone Terhubung</h4>
                <div class="border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase">Backbone Cable</th>
                                <th class="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase">Tube / Core</th>
                                <th class="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase">Notes</th>
                                <th class="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="rkConnectionsList" class="divide-y divide-gray-200 dark:divide-slate-700 text-xs">
                            <!-- Loaded via Ajax -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RK Delete Modal -->
<div id="confirmDeleteRkModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4">
    <div class="m-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-sm w-full p-6 text-center">
        <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
            <svg class="size-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Hapus RK</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Apakah Anda yakin ingin menghapus Rumah Kabel <strong id="deleteRkName" class="text-gray-800 dark:text-white"></strong>? Data akan dihapus permanen.
        </p>
        <form action="../actions/rk_actions.php" method="POST">
            <input type="hidden" name="action" value="delete_rk">
            <input type="hidden" name="id" id="deleteRkId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeModal('confirmDeleteRkModal')" class="py-2 px-4 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200">Batal</button>
                <button type="submit" class="py-2 px-4 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRkModal(mode) {
        if (mode === 'add') {
            document.getElementById('rkModalTitle').innerText = 'Tambah RK Baru';
            document.getElementById('rkAction').value = 'create_rk';
            document.getElementById('rkId').value = '';
            document.getElementById('rkName').value = '';
            document.getElementById('rkLocation').value = '';
            document.getElementById('rkLatitude').value = '';
            document.getElementById('rkLongitude').value = '';
            document.getElementById('rkNotes').value = '';
        }
        openModal('rkModal');
    }

    function editRk(data) {
        document.getElementById('rkModalTitle').innerText = 'Edit RK';
        document.getElementById('rkAction').value = 'update_rk';
        document.getElementById('rkId').value = data.id;
        document.getElementById('rkName').value = data.rk_name;
        document.getElementById('rkLocation').value = data.location_description || '';
        document.getElementById('rkLatitude').value = data.latitude || '';
        document.getElementById('rkLongitude').value = data.longitude || '';
        document.getElementById('rkNotes').value = data.notes || '';
        openModal('rkModal');
    }

    function viewRkDetail(id, name) {
        document.getElementById('detailRkId').value = id;
        document.getElementById('detailRkName').innerText = name;
        
        const list = document.getElementById('rkConnectionsList');
        list.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-center text-gray-500">Loading connections...</td></tr>';
        
        openModal('rkDetailModal');
        loadConnectionsList(id);
    }

    function loadConnectionsList(rkId) {
        const list = document.getElementById('rkConnectionsList');
        fetch(`../actions/ftth_data.php?action=get_rk_connections&rk_id=${rkId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.connections.length > 0) {
                    let html = '';
                    data.connections.forEach(conn => {
                        html += `
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2 font-semibold text-gray-800 dark:text-white">${conn.backbone_code}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Tube ${conn.tube_number} Core ${conn.core_number}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">${conn.notes || '-'}</td>
                                <td class="px-4 py-2 text-end">
                                    <button onclick="deleteConnection(${conn.id}, ${rkId})" class="py-1 px-2 rounded bg-red-50 text-red-600 hover:bg-red-100 font-semibold text-[10px]">Putus</button>
                                </td>
                            </tr>
                        `;
                    });
                    list.innerHTML = html;
                } else {
                    list.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-center text-gray-500">Belum ada core backbone terhubung.</td></tr>';
                }
            });
    }

    function deleteConnection(id, rkId) {
        if (confirm("Apakah Anda yakin ingin memutuskan sambungan backbone ini?")) {
            const formData = new FormData();
            formData.append('action', 'delete_connection');
            formData.append('id', id);
            formData.append('csrf_token', '<?= generate_csrf_token() ?>');

            fetch('../actions/rk_actions.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                loadConnectionsList(rkId);
            });
        }
    }

    function confirmDeleteRk(id, name) {
        document.getElementById('deleteRkId').value = id;
        document.getElementById('deleteRkName').innerText = name;
        openModal('confirmDeleteRkModal');
    }
</script>
