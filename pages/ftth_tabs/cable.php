<?php
// Fetch OLTs for dropdown
$olts = $pdo->query("SELECT id, olt_name FROM olts ORDER BY olt_name ASC")->fetchAll();

// Fetch RK Points for dropdown
$rks = $pdo->query("SELECT id, rk_name FROM rk_points ORDER BY rk_name ASC")->fetchAll();

// Fetch Backbone Cables
$backbones = $pdo->query("
    SELECT b.*, o.olt_name, op.port_number,
    (SELECT COUNT(*) FROM backbone_cores bc WHERE bc.backbone_id = b.id AND bc.status = 'active') as active_cores
    FROM backbones b
    LEFT JOIN olt_ports op ON b.olt_port_id = op.id
    LEFT JOIN olts o ON op.olt_id = o.id
    ORDER BY b.backbone_code ASC
")->fetchAll();

// Fetch Distribution Cables
$distributions = $pdo->query("
    SELECT d.*, rk.rk_name,
    (SELECT COUNT(*) FROM distribution_cores dc WHERE dc.distribution_id = d.id AND dc.status = 'active') as active_cores
    FROM distributions d
    LEFT JOIN rk_points rk ON d.rk_id = rk.id
    ORDER BY d.dist_code ASC
")->fetchAll();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen Kabel Jaringan (Backbone & Distribusi)</h2>
        <p class="text-xs text-gray-600 dark:text-gray-400">Kelola kabel optik multi-core, mapping kapasitas tube, dan status core.</p>
    </div>
</div>

<!-- Flash Message -->
<?php $flash = get_flash_message(); if ($flash): ?>
    <div class="mb-4 p-4 rounded-lg text-sm <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Tabs Navigation -->
<div class="border-b border-gray-200 dark:border-slate-700">
    <nav class="flex space-x-2" aria-label="Tabs" role="tablist">
        <button type="button" onclick="switchCableTab('backbone')" class="py-4 px-4 inline-flex items-center gap-x-2 border-b-2 font-semibold text-sm whitespace-nowrap transition-all border-blue-600 text-blue-600 dark:text-blue-500" id="backbone-tab">
            <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M5 12h14"/></svg>
            Kabel Backbone
        </button>
        <button type="button" onclick="switchCableTab('distribution')" class="py-4 px-4 inline-flex items-center gap-x-2 border-b-2 font-semibold text-sm whitespace-nowrap transition-all border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500" id="distribution-tab">
            <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><path d="M12 4v16"/></svg>
            Kabel Distribusi
        </button>
    </nav>
</div>

<!-- Panels -->
<div class="mt-3">
    <!-- Backbone Panel -->
    <div id="backbone-panel" role="tabpanel" aria-labelledby="backbone-tab" class="space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white">Daftar Kabel Backbone</h3>
            <button onclick="openBackboneModal('add')" class="py-2 px-3 inline-flex justify-center items-center gap-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold text-xs transition-all shadow-sm">
                Tambah Backbone
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Code</th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Source Port</th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Type / Capacity</th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Usage</th>
                        <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <?php if (empty($backbones)): ?>
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada kabel backbone terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($backbones as $bb): ?>
                            <?php 
                                $tot_cores = $bb['total_tubes'] * $bb['cores_per_tube'];
                                $usage = ($tot_cores > 0) ? ($bb['active_cores'] / $tot_cores) * 100 : 0;
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($bb['backbone_code']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                    <?= $bb['olt_name'] ? htmlspecialchars($bb['olt_name'] . ' - Port ' . $bb['port_number']) : '<span class="text-red-500 font-semibold">Disconnected</span>' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($bb['cable_type'] ?: 'Standard Optic') ?></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400"><?= $bb['total_tubes'] ?> Tube &times; <?= $bb['cores_per_tube'] ?> Core (Total <?= $tot_cores ?> Core)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        <?= $bb['active_cores'] ?>/<?= $tot_cores ?> Active (<?= number_format($usage, 0) ?>%)
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end">
                                    <button onclick="viewCoreMatrix('backbone', <?= $bb['id'] ?>, '<?= $bb['backbone_code'] ?>')" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 mr-2 transition-all">
                                        Core Map
                                    </button>
                                    <button onclick="editBackbone(<?= htmlspecialchars(json_encode($bb)) ?>)" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 mr-2">
                                        Edit
                                    </button>
                                    <button onclick="confirmDeleteCable('backbone', <?= $bb['id'] ?>, '<?= $bb['backbone_code'] ?>')" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-gray-200 dark:border-slate-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20">
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

    <!-- Distribution Panel -->
    <div id="distribution-panel" role="tabpanel" aria-labelledby="distribution-tab" class="space-y-6 hidden">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white">Daftar Kabel Distribusi</h3>
            <button onclick="openDistModal('add')" class="py-2 px-3 inline-flex justify-center items-center gap-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold text-xs transition-all shadow-sm">
                Tambah Distribusi
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Code</th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Source RK</th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Coverage Area</th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Cores</th>
                        <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <?php if (empty($distributions)): ?>
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada kabel distribusi terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($distributions as $db): ?>
                            <?php 
                                $tot_cores = $db['total_tubes'] * $db['cores_per_tube'];
                                $usage = ($tot_cores > 0) ? ($db['active_cores'] / $tot_cores) * 100 : 0;
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($db['dist_code']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                    <?= $db['rk_name'] ? htmlspecialchars($db['rk_name']) : '<span class="text-red-500 font-semibold">Disconnected</span>' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-800 dark:text-gray-200 font-medium">
                                    <?= htmlspecialchars($db['coverage_area'] ?: '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    <?= $tot_cores ?> Cores (<?= $db['active_cores'] ?> Active)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end">
                                    <button onclick="viewCoreMatrix('distribution', <?= $db['id'] ?>, '<?= $db['dist_code'] ?>')" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 mr-2 transition-all">
                                        Core Map
                                    </button>
                                    <button onclick="editDist(<?= htmlspecialchars(json_encode($db)) ?>)" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 mr-2">
                                        Edit
                                    </button>
                                    <button onclick="confirmDeleteCable('distribution', <?= $db['id'] ?>, '<?= $db['dist_code'] ?>')" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-gray-200 dark:border-slate-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20">
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
</div>

<!-- Backbone Form Modal -->
<div id="bbModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="bbModalTitle" class="font-bold text-gray-800 dark:text-white">Tambah Kabel Backbone</h3>
            <button onclick="closeModal('bbModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="../actions/backbone_actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="bbAction" value="create">
            <input type="hidden" name="id" id="bbId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Kode Backbone</label>
                    <input type="text" name="backbone_code" id="bbCode" required placeholder="e.g. BB-01" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Pilih OLT Asal (Opsional)</label>
                    <select id="bbOltSelect" onchange="loadOltPorts(this.value, 'bbPortSelect')" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">-- Pilih OLT --</option>
                        <?php foreach ($olts as $o): ?>
                            <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['olt_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Pilih Port OLT (Opsional)</label>
                    <select name="olt_port_id" id="bbPortSelect" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">-- Pilih Port --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Tipe Kabel</label>
                    <input type="text" name="cable_type" id="bbCableType" placeholder="e.g. Single Mode 24 Core" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div id="bbCapacityGrid" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Jumlah Tube</label>
                    <input type="number" name="total_tubes" id="bbTubes" value="2" min="1" max="12" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Core per Tube</label>
                    <input type="number" name="cores_per_tube" id="bbCores" value="6" min="1" max="24" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Deskripsi Jalur/Rute</label>
                <textarea name="route_description" id="bbRoute" rows="2" placeholder="e.g. Ruang Server -> RK-A Utama" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Catatan</label>
                <textarea name="notes" id="bbNotes" rows="2" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('bbModal')" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold border rounded-lg bg-white text-gray-800 dark:bg-slate-800 dark:border-slate-700 dark:text-white">Batal</button>
                <button type="submit" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Distribution Form Modal -->
<div id="distModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="distModalTitle" class="font-bold text-gray-800 dark:text-white">Tambah Kabel Distribusi</h3>
            <button onclick="closeModal('distModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="../actions/distribution_actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="distAction" value="create">
            <input type="hidden" name="id" id="distId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Kode Kabel Distribusi</label>
                    <input type="text" name="dist_code" id="distCode" required placeholder="e.g. DIST-A01" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Pilih RK Asal</label>
                    <select name="rk_id" id="distRkSelect" required class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">-- Pilih RK --</option>
                        <?php foreach ($rks as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['rk_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Tipe Kabel</label>
                <input type="text" name="cable_type" id="distCableType" placeholder="e.g. FIG-8 12 Core SM" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div id="distCapacityGrid" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Jumlah Tube</label>
                    <input type="number" name="total_tubes" id="distTubes" value="1" min="1" max="12" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Core per Tube</label>
                    <input type="number" name="cores_per_tube" id="distCores" value="12" min="1" max="24" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Coverage Area / Wilayah</label>
                <input type="text" name="coverage_area" id="distCoverage" placeholder="e.g. Perumahan X Blok C & D" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Catatan</label>
                <textarea name="notes" id="distNotes" rows="2" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('distModal')" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold border rounded-lg bg-white text-gray-800 dark:bg-slate-800 dark:border-slate-700 dark:text-white">Batal</button>
                <button type="submit" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Core Matrix Drawer / Modal -->
<div id="coreMatrixModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-2xl w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Peta Core: <span id="matrixCableCode" class="text-blue-600"></span></h3>
            <button onclick="closeModal('coreMatrixModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <div id="coreContainer" class="grid gap-4 max-h-[400px] overflow-y-auto pr-2">
                <!-- Tube rows and Core cells will be loaded dynamically here -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Core Modal (Inside Matrix) -->
<div id="editCoreModal" class="hidden fixed inset-0 z-[70] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-sm w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Core Property</h3>
            <button onclick="closeModal('editCoreModal')" class="size-8 inline-flex justify-center items-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editCoreForm" action="" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_core">
            <input type="hidden" name="core_id" id="editCoreId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Status Core</label>
                <select name="status" id="editCoreStatus" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                    <option value="idle">Idle / Kosong</option>
                    <option value="active">Active (Tersambung)</option>
                    <option value="reserved">Reserved / Booking</option>
                    <option value="fault">Fault / Putus (Damage)</option>
                </select>
            </div>

            <!-- OLT Mapping Section for Backbone Cores -->
            <div id="editCoreOltSection" class="space-y-4" style="display: none;">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">OLT Asal (Opsional)</label>
                        <select id="editCoreOltSelect" onchange="loadOltPorts(this.value, 'editCorePortSelect')" class="py-2 px-2.5 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-xs bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($olts as $o): ?>
                                <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['olt_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Port OLT (Opsional)</label>
                        <select name="olt_port_id" id="editCorePortSelect" class="py-2 px-2.5 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-xs bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                            <option value="">-- Pilih Port --</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-gray-800 dark:text-white">Catatan Core</label>
                <textarea name="notes" id="editCoreNotes" rows="2" class="py-2 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="e.g. Splice ke DP-C01"></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('editCoreModal')" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold border rounded-lg bg-white text-gray-800 dark:bg-slate-800 dark:border-slate-700 dark:text-white">Batal</button>
                <button type="submit" class="py-2 px-3 inline-flex justify-center items-center text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Cable Delete Modal -->
<div id="confirmDeleteCableModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4">
    <div class="m-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl max-w-sm w-full p-6 text-center">
        <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
            <svg class="size-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Hapus Kabel</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Apakah Anda yakin ingin menghapus kabel <strong id="deleteCableName" class="text-gray-800 dark:text-white"></strong>? Data core beserta statusnya akan dihapus permanen.
        </p>
        <form id="deleteCableForm" action="" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteCableId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeModal('confirmDeleteCableModal')" class="py-2 px-4 text-sm font-semibold rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200">Batal</button>
                <button type="submit" class="py-2 px-4 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function loadOltPorts(oltId, targetSelectId, selectedPortId = '') {
        const select = document.getElementById(targetSelectId);
        select.innerHTML = '<option value="">-- Loading Port --</option>';
        if (!oltId) {
            select.innerHTML = '<option value="">-- Pilih Port --</option>';
            return;
        }

        fetch(`../actions/ftth_data.php?action=get_olt_ports&olt_id=${oltId}&filter=idle`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '<option value="">-- Pilih Port --</option>';
                    data.ports.forEach(port => {
                        html += `<option value="${port.id}" ${port.id == selectedPortId ? 'selected' : ''}>Port ${port.port_number} (${port.port_label || port.port_type})</option>`;
                    });
                    select.innerHTML = html;
                } else {
                    select.innerHTML = '<option value="">-- Gagal memuat port --</option>';
                }
            });
    }

    function openBackboneModal(mode) {
        if (mode === 'add') {
            document.getElementById('bbModalTitle').innerText = 'Tambah Kabel Backbone';
            document.getElementById('bbAction').value = 'create';
            document.getElementById('bbId').value = '';
            document.getElementById('bbCode').value = '';
            document.getElementById('bbOltSelect').value = '';
            document.getElementById('bbPortSelect').innerHTML = '<option value="">-- Pilih Port --</option>';
            document.getElementById('bbCableType').value = '';
            document.getElementById('bbTubes').value = '2';
            document.getElementById('bbCores').value = '6';
            document.getElementById('bbCapacityGrid').style.display = 'grid';
            document.getElementById('bbRoute').value = '';
            document.getElementById('bbNotes').value = '';
        }
        openModal('bbModal');
    }

    function editBackbone(data) {
        document.getElementById('bbModalTitle').innerText = 'Edit Kabel Backbone';
        document.getElementById('bbAction').value = 'update';
        document.getElementById('bbId').value = data.id;
        document.getElementById('bbCode').value = data.backbone_code;
        document.getElementById('bbCableType').value = data.cable_type || '';
        document.getElementById('bbCapacityGrid').style.display = 'none'; // Cannot edit core count
        document.getElementById('bbRoute').value = data.route_description || '';
        document.getElementById('bbNotes').value = data.notes || '';
        
        // Populate OLT and Port
        if (data.olt_id) {
            document.getElementById('bbOltSelect').value = data.olt_id;
            loadOltPorts(data.olt_id, 'bbPortSelect', data.olt_port_id);
        } else {
            document.getElementById('bbOltSelect').value = '';
            document.getElementById('bbPortSelect').innerHTML = '<option value="">-- Pilih Port --</option>';
        }
        openModal('bbModal');
    }

    function openDistModal(mode) {
        if (mode === 'add') {
            document.getElementById('distModalTitle').innerText = 'Tambah Kabel Distribusi';
            document.getElementById('distAction').value = 'create';
            document.getElementById('distId').value = '';
            document.getElementById('distCode').value = '';
            document.getElementById('distRkSelect').value = '';
            document.getElementById('distCableType').value = '';
            document.getElementById('distTubes').value = '1';
            document.getElementById('distCores').value = '12';
            document.getElementById('distCapacityGrid').style.display = 'grid';
            document.getElementById('distCoverage').value = '';
            document.getElementById('distNotes').value = '';
        }
        openModal('distModal');
    }

    function editDist(data) {
        document.getElementById('distModalTitle').innerText = 'Edit Kabel Distribusi';
        document.getElementById('distAction').value = 'update';
        document.getElementById('distId').value = data.id;
        document.getElementById('distCode').value = data.dist_code;
        document.getElementById('distRkSelect').value = data.rk_id || '';
        document.getElementById('distCableType').value = data.cable_type || '';
        document.getElementById('distCapacityGrid').style.display = 'none'; // Cannot edit core count
        document.getElementById('distCoverage').value = data.coverage_area || '';
        document.getElementById('distNotes').value = data.notes || '';
        openModal('distModal');
    }

    function viewCoreMatrix(type, id, code) {
        document.getElementById('matrixCableCode').innerText = code;
        const container = document.getElementById('coreContainer');
        container.innerHTML = '<p class="text-center text-gray-500 py-4">Loading core mapping...</p>';
        openModal('coreMatrixModal');

        const action = type === 'backbone' ? 'get_backbone_cores' : 'get_dist_cores';
        const param = type === 'backbone' ? `backbone_id=${id}` : `distribution_id=${id}`;

        fetch(`../actions/ftth_data.php?action=${action}&${param}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.cores.length > 0) {
                    // Group cores by tube_number
                    const tubes = {};
                    data.cores.forEach(core => {
                        if (!tubes[core.tube_number]) tubes[core.tube_number] = [];
                        tubes[core.tube_number].push(core);
                    });

                    let html = '';
                    Object.keys(tubes).forEach(tNum => {
                        html += `
                            <div class="bg-gray-50 dark:bg-slate-900 p-4 rounded-lg border border-gray-100 dark:border-slate-800">
                                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase">Tube ${tNum}</h4>
                                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                        `;
                        tubes[tNum].forEach(core => {
                            let color = 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
                            if (core.status === 'active') color = 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 border-blue-200 dark:border-blue-800';
                            if (core.status === 'reserved') color = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800';
                            if (core.status === 'fault') color = 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 border-red-200 dark:border-red-800';

                            let displayLabel = core.notes || core.status;
                            if (type === 'backbone' && core.olt_name && core.port_number) {
                                displayLabel += ` (${core.olt_name} P${core.port_number})`;
                            }

                            html += `
                                <div onclick="openEditCore('${type}', ${core.id}, '${core.status}', '${core.notes || ''}', ${core.olt_id || 'null'}, ${core.olt_port_id || 'null'})" class="p-2 border rounded text-center cursor-pointer hover:shadow-sm text-xs font-semibold transition-all ${color}">
                                    C${core.core_number}
                                    <div class="text-[8px] opacity-60 font-normal truncate" title="${core.notes || ''}">${displayLabel}</div>
                                </div>
                            `;
                        });
                        html += `
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="text-center text-red-500 py-4">Gagal memuat core.</p>';
                }
            });
    }

    function openEditCore(type, id, status, notes, oltId = null, oltPortId = null) {
        document.getElementById('editCoreId').value = id;
        document.getElementById('editCoreStatus').value = status;
        document.getElementById('editCoreNotes').value = notes;
        
        // Dynamically adjust form action
        const form = document.getElementById('editCoreForm');
        form.action = type === 'backbone' ? '../actions/backbone_actions.php' : '../actions/distribution_actions.php';
        
        const oltSection = document.getElementById('editCoreOltSection');
        if (type === 'backbone') {
            oltSection.style.display = 'block';
            document.getElementById('editCoreOltSelect').value = oltId || '';
            if (oltId) {
                loadOltPorts(oltId, 'editCorePortSelect', oltPortId);
            } else {
                document.getElementById('editCorePortSelect').innerHTML = '<option value="">-- Pilih Port --</option>';
            }
        } else {
            oltSection.style.display = 'none';
            document.getElementById('editCoreOltSelect').value = '';
            document.getElementById('editCorePortSelect').innerHTML = '<option value="">-- Pilih Port --</option>';
        }
        
        openModal('editCoreModal');
    }

    function confirmDeleteCable(type, id, code) {
        document.getElementById('deleteCableId').value = id;
        document.getElementById('deleteCableName').innerText = code;
        
        const form = document.getElementById('deleteCableForm');
        form.action = type === 'backbone' ? '../actions/backbone_actions.php' : '../actions/distribution_actions.php';
        
        openModal('confirmDeleteCableModal');
    }

    function switchCableTab(tabName) {
        const bbTab = document.getElementById('backbone-tab');
        const distTab = document.getElementById('distribution-tab');
        const bbPanel = document.getElementById('backbone-panel');
        const distPanel = document.getElementById('distribution-panel');

        if (tabName === 'backbone') {
            bbTab.className = "py-4 px-4 inline-flex items-center gap-x-2 border-b-2 font-semibold text-sm whitespace-nowrap transition-all border-blue-600 text-blue-600 dark:text-blue-500";
            distTab.className = "py-4 px-4 inline-flex items-center gap-x-2 border-b-2 font-semibold text-sm whitespace-nowrap transition-all border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500";
            
            bbPanel.classList.remove('hidden');
            distPanel.classList.add('hidden');
        } else {
            distTab.className = "py-4 px-4 inline-flex items-center gap-x-2 border-b-2 font-semibold text-sm whitespace-nowrap transition-all border-blue-600 text-blue-600 dark:text-blue-500";
            bbTab.className = "py-4 px-4 inline-flex items-center gap-x-2 border-b-2 font-semibold text-sm whitespace-nowrap transition-all border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500";
            
            distPanel.classList.remove('hidden');
            bbPanel.classList.add('hidden');
        }
    }
</script>
