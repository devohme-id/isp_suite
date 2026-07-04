<?php
require_once '../config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: ftth_network.php?tab=dp');
    exit;
}

// Fetch DP Details and Feed Core
$stmt = $pdo->prepare("
    SELECT dp.*, 
    (SELECT COUNT(*) FROM customers c WHERE c.dp_id = dp.id) as used_ports 
    FROM drop_points dp 
    WHERE dp.id = ?
");
$stmt->execute([$id]);
$dp = $stmt->fetch();

if (!$dp) {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Drop Point tidak ditemukan.'];
    header('Location: ftth_network.php?tab=dp');
    exit;
}

$page_title = 'Drop Point Detail: ' . htmlspecialchars($dp['dp_name']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch Customers on this DP for Port Mapping
$stmt_cust = $pdo->prepare("SELECT id, name, dp_port, address FROM customers WHERE dp_id = ? ORDER BY dp_port ASC");
$stmt_cust->execute([$id]);
$customers = $stmt_cust->fetchAll();

// Map customers to ports
$port_map = [];
foreach ($customers as $c) {
    if ($c['dp_port']) {
        $port_map[$c['dp_port']] = $c;
    }
}

$usage_percent = ($dp['total_ports'] > 0) ? ($dp['used_ports'] / $dp['total_ports']) * 100 : 0;
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-6">
        
        <!-- Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                 <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="ftth_network.php?tab=dp" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                DP Management
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Detail DP</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($dp['dp_code']) ?> &mdash; <?= htmlspecialchars($dp['dp_name']) ?></h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Lihat port mapping pelanggan dan visualisasi rantai distribusi optik upstream.</p>
            </div>
            <a href="ftth_network.php?tab=dp" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 font-medium text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700 transition-all">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
        </div>

        <!-- Upstream Network Path Visualizer -->
        <div id="pathContainer" class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
            <h3 class="font-bold text-gray-800 dark:text-white mb-4">Jalur Upstream Jaringan Fiber Optic</h3>
            
            <div id="pathDisplay" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 py-2">
                <!-- Will be loaded dynamically via fetch -->
                <div class="text-center w-full text-gray-500 py-4">Loading upstream link path...</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Info & Stats -->
            <div class="space-y-6">
                <!-- Info Card -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">Informasi Drop Point</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Kode DP</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($dp['dp_code']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Nama DP</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($dp['dp_name']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Zone / Area</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($dp['zone_area'] ?: '-') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Total Port</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= $dp['total_ports'] ?> Port</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Terpakai</span>
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400"><?= $dp['used_ports'] ?> Port</span>
                        </div>
                        <div class="pt-2">
                             <div class="flex justify-between mb-1">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Kapasitas</span>
                                <span class="text-xs font-medium text-gray-800 dark:text-white"><?= number_format($usage_percent, 1) ?>%</span>
                             </div>
                             <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $usage_percent ?>%"></div>
                             </div>
                        </div>
                    </div>
                   
                   <?php if($dp['latitude'] && $dp['longitude']): ?>
                    <div class="mt-6">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Lokasi Koordinat</h4>
                        <div class="flex items-center gap-2 text-sm text-gray-800 dark:text-white bg-gray-50 dark:bg-slate-900 p-3 rounded-lg border border-gray-100 dark:border-slate-700">
                            <svg class="size-4 text-red-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25g-3a7.5 7.5 0 1115 0z"/></svg>
                            <span class="truncate"><?= htmlspecialchars($dp['latitude']) ?>, <?= htmlspecialchars($dp['longitude']) ?></span>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $dp['latitude'] ?>,<?= $dp['longitude'] ?>" target="_blank" class="ml-auto text-blue-600 hover:text-blue-700 text-xs font-semibold">Buka Maps</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($dp['notes']): ?>
                    <div class="mt-4">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Catatan Lapangan</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-pretty"><?= nl2br(htmlspecialchars($dp['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Port Visualization -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Port Grid -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">Visualisasi Port DP</h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        <?php for($i = 1; $i <= $dp['total_ports']; $i++): ?>
                            <?php 
                                $is_occupied = isset($port_map[$i]);
                                $customer = $is_occupied ? $port_map[$i] : null;
                                
                                $base_classes = "relative flex flex-col justify-between p-2.5 border rounded-lg transition-all duration-200 hover:shadow-md h-22 group text-left";
                                
                                if ($is_occupied) {
                                    $bg_class = 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700';
                                    $link_wrap_start = '<a href="customer_detail.php?id=' . $customer['id'] . '" class="' . $base_classes . ' ' . $bg_class . '">';
                                    $link_wrap_end = '</a>';
                                } else {
                                    $bg_class = 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 opacity-80 hover:opacity-100';
                                    $link_wrap_start = '<div class="' . $base_classes . ' ' . $bg_class . '">';
                                    $link_wrap_end = '</div>';
                                }
                            ?>
                            
                            <?= $link_wrap_start ?>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">#<?= $i ?></span>
                                    <?php if($is_occupied): ?>
                                       <span class="flex size-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]"></span>
                                    <?php else: ?>
                                       <span class="flex size-2 rounded-full bg-green-500 opacity-60"></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-1 flex flex-col justify-center">
                                    <?php if($is_occupied): ?>
                                        <p class="text-xs font-bold text-gray-800 dark:text-white leading-tight line-clamp-2" title="<?= htmlspecialchars($customer['name']) ?>">
                                            <?= htmlspecialchars($customer['name']) ?>
                                        </p>
                                        <p class="text-[9px] text-gray-500 dark:text-gray-400 truncate mt-0.5" title="<?= htmlspecialchars($customer['address']) ?>">
                                            <?= htmlspecialchars($customer['address'] ?? '-') ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 text-center">Available</p>
                                    <?php endif; ?>
                                </div>
                            <?= $link_wrap_end ?>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Customer List -->
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h3 class="font-bold text-gray-800 dark:text-white">Daftar Pelanggan di Drop Point Ini</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                             <thead class="bg-gray-50 dark:bg-slate-900">
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Port</th>
                                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama Pelanggan</th>
                                    <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Alamat</th>
                                    <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                <?php if(empty($customers)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pelanggan terhubung.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($customers as $c): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800 dark:text-white">
                                            Port <?= $c['dp_port'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 font-medium">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="<?= htmlspecialchars($c['address']) ?>">
                                            <?= htmlspecialchars($c['address'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                             <a href="customer_detail.php?id=<?= $c['id'] ?>" class="inline-flex items-center gap-x-1 text-sm text-blue-600 decoration-2 hover:underline font-semibold dark:text-blue-500">
                                                Detail
                                                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                            </a>
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
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const dpId = <?= $id ?>;
        const display = document.getElementById('pathDisplay');

        // Fetch network link path tracing
        fetch(`../actions/ftth_data.php?action=get_network_path&dp_id=${dpId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.path) {
                    const p = data.path;
                    let html = '';

                    // 1. OLT Node
                    if (p.olt_name) {
                        html += `
                            <div class="flex-1 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 p-4 rounded-xl flex items-center gap-3">
                                <div class="size-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center font-bold text-xs">OLT</div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold uppercase">OLT Pusat</span>
                                    <strong class="text-sm text-gray-800 dark:text-white">${p.olt_name}</strong>
                                    <span class="block text-[10px] text-gray-500 dark:text-gray-400">Port ${p.olt_port} (${p.olt_port_type})</span>
                                </div>
                            </div>
                            <div class="hidden md:flex items-center text-gray-300 dark:text-slate-700">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="flex-1 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 p-4 rounded-xl flex items-center gap-3 opacity-60">
                                <div class="size-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-xs">OLT</div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold uppercase">OLT Pusat</span>
                                    <strong class="text-sm text-red-600">Disconnected</strong>
                                </div>
                            </div>
                            <div class="hidden md:flex items-center text-gray-300">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        `;
                    }

                    // 2. Backbone Node
                    if (p.backbone_code) {
                        html += `
                            <div class="flex-1 bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800 p-4 rounded-xl flex items-center gap-3">
                                <div class="size-8 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 flex items-center justify-center font-bold text-xs">BB</div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold uppercase">Backbone Link</span>
                                    <strong class="text-sm text-gray-800 dark:text-white">${p.backbone_code}</strong>
                                    <span class="block text-[10px] text-gray-500 dark:text-gray-400">Tube ${p.bb_tube} Core ${p.bb_core}</span>
                                </div>
                            </div>
                            <div class="hidden md:flex items-center text-gray-300 dark:text-slate-700">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        `;
                    }

                    // 3. RK Node
                    if (p.rk_name) {
                        html += `
                            <div class="flex-1 bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-800 p-4 rounded-xl flex items-center gap-3">
                                <div class="size-8 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400 flex items-center justify-center font-bold text-xs">RK</div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold uppercase">Rumah Kabel</span>
                                    <strong class="text-sm text-gray-800 dark:text-white">${p.rk_name}</strong>
                                    <span class="block text-[10px] text-gray-500 dark:text-gray-400">${p.rk_location || 'No Location'}</span>
                                </div>
                            </div>
                            <div class="hidden md:flex items-center text-gray-300 dark:text-slate-700">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        `;
                    }

                    // 4. Distribution Cable Node
                    if (p.dist_code) {
                        html += `
                            <div class="flex-1 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-800 p-4 rounded-xl flex items-center gap-3">
                                <div class="size-8 rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">DIST</div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold uppercase">Kabel Distribusi</span>
                                    <strong class="text-sm text-gray-800 dark:text-white">${p.dist_code}</strong>
                                    <span class="block text-[10px] text-gray-500 dark:text-gray-400">Tube ${p.dist_tube} Core ${p.dist_core}</span>
                                </div>
                            </div>
                        `;
                    }

                    display.innerHTML = html;
                } else {
                    display.innerHTML = '<div class="text-center w-full text-red-500 py-4 font-semibold">Gagal melacak link upstream. Hubungkan Drop Point ke Core Distribusi terlebih dahulu.</div>';
                }
            });
    });
</script>

<?php require_once '../includes/footer.php'; ?>
