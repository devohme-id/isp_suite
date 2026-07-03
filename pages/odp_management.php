<?php
require_once '../config.php';
require_login();
$page_title = 'ODP Management - Data Center';
include '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch ODPs with capacity usage
$search = clean_input($_GET['search'] ?? '');
$sql_base = "SELECT o.*, 
            (SELECT COUNT(*) FROM customers c WHERE c.odp_id = o.id) as used_ports 
            FROM odp_points o WHERE 1=1";
$params = [];

if ($search) {
    $sql_base .= " AND (o.odp_name LIKE ? OR o.zone_area LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql_base .= " ORDER BY o.odp_name ASC";

try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $odps = $stmt->fetchAll();
} catch (PDOException $e) {
    $odps = [];
}
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-6">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
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
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">ODP Management</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Optical Drop Points (ODP)</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage infrastructure capacity and location mapping.</p>
            </div>
            <button onclick="openOdpModal('add')" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-transparent font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-all text-sm">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                New ODP
            </button>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm mb-6">
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                         <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" name="search" placeholder="Cari nama ODP atau wilayah..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
                </div>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                    Filter Data
                </button>
            </form>
        </div>

        <!-- ODP Table -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ODP Info</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Zone / Area</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Capacity Usage</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <?php if (empty($odps)): ?>
                            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No ODP found.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($odps as $odp): ?>
                                <?php 
                                    $usage_percent = ($odp['total_ports'] > 0) ? ($odp['used_ports'] / $odp['total_ports']) * 100 : 0;
                                    $color_class = $usage_percent >= 80 ? 'bg-red-500' : ($usage_percent >= 50 ? 'bg-yellow-500' : 'bg-green-500');
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <?= $no++ ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-x-3">
                                            <div class="shrink-0">
                                                <span class="inline-flex items-center justify-center size-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="12" x="3" y="10" rx="2"/><path d="M12 10v12"/><path d="M7 10v12"/><path d="M17 10v12"/><path d="M7 2h10"/><path d="M12 2v8"/></svg>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="block font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($odp['odp_name']) ?></span>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($odp['latitude']) ?>, <?= htmlspecialchars($odp['longitude']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                        <?= htmlspecialchars($odp['zone_area']) ?>
                                        <div class="text-xs text-gray-400 truncate max-w-[200px]" title="<?= htmlspecialchars($odp['notes']) ?>"><?= htmlspecialchars($odp['notes']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-x-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 w-12"><?= $odp['used_ports'] ?>/<?= $odp['total_ports'] ?></span>
                                            <div class="flex w-full h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700 max-w-[100px]">
                                                <div class="flex flex-col justify-center overflow-hidden <?= $color_class ?>" role="progressbar" style="width: <?= $usage_percent ?>%" aria-valuenow="<?= $usage_percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end">
                                        <!-- Detail Button -->
                                        <a href="odp_detail.php?id=<?= $odp['id'] ?>" class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-500 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            View
                                        </a>

                                        <button onclick="editODP(<?= htmlspecialchars(json_encode($odp)) ?>)" class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 mr-2">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                            Edit
                                        </button>
                                        <button onclick="confirmDelete(<?= $odp['id'] ?>)" class="py-2 px-2.5 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
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
    </div>
</div>

<!-- ODP Modal -->
<div id="odpModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
    <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-lg w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 id="modalTitle" class="font-bold text-gray-800 dark:text-white">Add New ODP</h3>
            <button type="button" onclick="closeModal('odpModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/odp_actions.php" method="POST" class="p-6 overflow-y-auto">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="odpId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="grid grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">ODP Name</label>
                    <input type="text" name="odp_name" id="odpName" required placeholder="ODP-A-01" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Total Ports</label>
                    <input type="number" name="total_ports" id="totalPorts" required value="8" min="1" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Zone / Area</label>
                <input type="text" name="zone_area" id="zoneArea" placeholder="e.g. Perumahan X Blok B" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
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
                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Additional information..."></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('odpModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openOdpModal(mode, data = null) {
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Add New ODP';
            document.getElementById('formAction').value = 'create';
            document.getElementById('odpId').value = '';
            document.getElementById('odpName').value = '';
            document.getElementById('totalPorts').value = '8';
            document.getElementById('zoneArea').value = '';
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            document.getElementById('notes').value = '';
        }
        openModal('odpModal');
    }

    function editODP(data) {
        document.getElementById('modalTitle').innerText = 'Edit ODP';
        document.getElementById('formAction').value = 'update';
        document.getElementById('odpId').value = data.id;
        document.getElementById('odpName').value = data.odp_name;
        document.getElementById('totalPorts').value = data.total_ports;
        document.getElementById('zoneArea').value = data.zone_area || '';
        document.getElementById('latitude').value = data.latitude || '';
        document.getElementById('longitude').value = data.longitude || '';
        document.getElementById('notes').value = data.notes || '';
        openModal('odpModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_name').innerText = name || 'ODP ini';
        openModal('confirmDeleteModal');
    }
</script>

<!-- Confirm Delete Modal -->
<div id="confirmDeleteModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-sm w-full">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                <svg class="size-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Apakah anda yakin ingin menghapus <strong id="delete_name" class="text-gray-800 dark:text-white"></strong>? Data yang dihapus tidak dapat dikembalikan.
            </p>
            <form action="../actions/odp_actions.php" method="POST">
                <input type="hidden" name="action" value="delete">
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

<?php require_once '../includes/footer.php'; ?>
