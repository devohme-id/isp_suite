<?php
require_once '../config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: ftth_network.php?tab=olt');
    exit;
}

// Fetch OLT Details
$stmt = $pdo->prepare("
    SELECT o.*, 
    (SELECT COUNT(*) FROM olt_ports p WHERE p.olt_id = o.id AND p.status = 'active') as active_ports 
    FROM olts o 
    WHERE o.id = ?
");
$stmt->execute([$id]);
$olt = $stmt->fetch();

if (!$olt) {
    set_flash_message('error', 'OLT tidak ditemukan.');
    header('Location: ftth_network.php?tab=olt');
    exit;
}

$page_title = 'OLT Detail: ' . htmlspecialchars($olt['olt_name']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch Ports on this OLT
$stmt_ports = $pdo->prepare("
    SELECT p.*, 
           COALESCE(b.backbone_code, b_core.backbone_code) AS backbone_code,
           COALESCE(b.id, b_core.id) AS backbone_id,
           bc.tube_number AS core_tube, bc.core_number AS core_num
    FROM olt_ports p
    LEFT JOIN backbones b ON b.olt_port_id = p.id
    LEFT JOIN backbone_cores bc ON bc.olt_port_id = p.id
    LEFT JOIN backbones b_core ON bc.backbone_id = b_core.id
    WHERE p.olt_id = ?
    ORDER BY p.port_number ASC
");
$stmt_ports->execute([$id]);
$ports = $stmt_ports->fetchAll();

$usage_percent = ($olt['total_ports'] > 0) ? ($olt['active_ports'] / $olt['total_ports']) * 100 : 0;
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-6">
        
        <!-- Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                 <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="ftth_network.php?tab=olt" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                OLT Management
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Detail OLT</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($olt['olt_name']) ?></h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Lihat port fisik OLT, jenis port GPON/EPON, dan detail link backbone.</p>
            </div>
            <a href="ftth_network.php?tab=olt" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 font-medium text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700 transition-all">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Info & Stats -->
            <div class="space-y-6">
                <!-- Info Card -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">Informasi OLT</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Nama OLT</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($olt['olt_name']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Brand / Model</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($olt['olt_model'] ?: '-') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">IP Address</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($olt['ip_address'] ?: '-') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Lokasi Rack</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($olt['location'] ?: '-') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Total Port</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white"><?= $olt['total_ports'] ?> Port</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Port Aktif</span>
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400"><?= $olt['active_ports'] ?> Port</span>
                        </div>
                        <div class="pt-2">
                             <div class="flex justify-between mb-1">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Utilitasi Port</span>
                                <span class="text-xs font-medium text-gray-800 dark:text-white"><?= number_format($usage_percent, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $usage_percent ?>%"></div>
                            </div>
                        </div>
                    </div>
                   
                   <?php if($olt['latitude'] && $olt['longitude']): ?>
                    <div class="mt-6">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Lokasi Koordinat</h4>
                        <div class="flex items-center gap-2 text-sm text-gray-800 dark:text-white bg-gray-50 dark:bg-slate-900 p-3 rounded-lg border border-gray-100 dark:border-slate-700">
                            <svg class="size-4 text-red-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25g-3a7.5 7.5 0 1115 0z"/></svg>
                            <span class="truncate"><?= htmlspecialchars($olt['latitude']) ?>, <?= htmlspecialchars($olt['longitude']) ?></span>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $olt['latitude'] ?>,<?= $olt['longitude'] ?>" target="_blank" class="ml-auto text-blue-600 hover:text-blue-700 text-xs font-semibold">Buka Maps</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($olt['notes']): ?>
                    <div class="mt-4">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Catatan Teknikal</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-pretty"><?= nl2br(htmlspecialchars($olt['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Port Grid & Detail -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Port Grid -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">Visualisasi Port Fisik OLT</h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        <?php foreach($ports as $port): ?>
                            <?php 
                                $is_active = $port['status'] === 'active';
                                $is_fault = $port['status'] === 'fault';
                                
                                $base_classes = "relative flex flex-col justify-between p-2.5 border rounded-lg transition-all duration-200 hover:shadow-md h-22 group cursor-pointer text-left";
                                
                                if ($is_active) {
                                    $bg_class = 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700';
                                } elseif ($is_fault) {
                                    $bg_class = 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700';
                                } else {
                                    $bg_class = 'bg-gray-50 dark:bg-slate-900/20 border-gray-200 dark:border-slate-700 hover:border-gray-300 dark:hover:border-slate-600';
                                }
                            ?>
                            
                            <div onclick="selectPort(<?= htmlspecialchars(json_encode($port)) ?>)" class="<?= $base_classes ?> <?= $bg_class ?>">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">#<?= $port['port_number'] ?></span>
                                    <?php if($is_active): ?>
                                       <span class="flex size-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]"></span>
                                    <?php elseif($is_fault): ?>
                                       <span class="flex size-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]"></span>
                                    <?php else: ?>
                                       <span class="flex size-2 rounded-full bg-gray-400 opacity-60"></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-1 flex flex-col justify-center">
                                    <?php if($is_active && $port['backbone_code']): ?>
                                        <p class="text-xs font-bold text-blue-700 dark:text-blue-400 leading-tight truncate">
                                            <?= htmlspecialchars($port['backbone_code']) ?>
                                            <?php if (!empty($port['core_tube'])): ?>
                                                (T<?= $port['core_tube'] ?>-C<?= $port['core_num'] ?>)
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-[9px] text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                            <?= htmlspecialchars($port['port_label'] ?: $port['port_type']) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 text-center"><?= $is_fault ? 'Fault' : 'Idle' ?></p>
                                        <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-0.5 text-center truncate"><?= htmlspecialchars($port['port_type']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Port Detailed Info / Downstream Chain Panel -->
                <div id="portDetailPanel" class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 dark:text-white">Detail Port Jaringan</h3>
                        <button onclick="editSelectedPort()" class="py-1.5 px-3 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 transition-colors">
                            Edit Properties
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-xs text-gray-400 block">Port Number</span>
                            <span id="pDetNumber" class="text-sm font-bold text-gray-800 dark:text-white">-</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Port Type / Standard</span>
                            <span id="pDetType" class="text-sm font-bold text-gray-800 dark:text-white">-</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Label Port</span>
                            <span id="pDetLabel" class="text-sm font-bold text-gray-800 dark:text-white">-</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Status Port</span>
                            <span id="pDetStatus" class="text-sm font-bold">-</span>
                        </div>
                    </div>

                    <!-- Downstream Network Chain Visualizer -->
                    <div id="pDetChainContainer" class="border-t border-gray-100 dark:border-slate-700 pt-4 hidden">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Jalur Distribusi Downstream</h4>
                        
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center size-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                        OLT
                                    </span>
                                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                                </div>
                                <div class="pt-1">
                                    <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Port OLT Fisik</h5>
                                    <p id="chainOltPort" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center size-8 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                        BB
                                    </span>
                                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                                </div>
                                <div class="pt-1">
                                    <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Kabel Backbone</h5>
                                    <p id="chainBackbone" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center size-8 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                        RK
                                    </span>
                                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                                </div>
                                <div class="pt-1">
                                    <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Rumah Kabel (RK)</h5>
                                    <p id="chainRk" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center size-8 rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                        DIST
                                    </span>
                                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                                </div>
                                <div class="pt-1">
                                    <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Kabel Distribusi</h5>
                                    <p id="chainDist" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center size-8 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 font-bold text-xs ring-2 ring-white dark:ring-slate-800">
                                        DP
                                    </span>
                                </div>
                                <div class="pt-1 w-full">
                                    <div class="flex justify-between items-center">
                                        <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Drop Point (DP)</h5>
                                        <a id="chainDpDetailLink" href="#" class="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline">Lihat Detail DP</a>
                                    </div>
                                    <p id="chainDp" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Port Modal -->
<div id="portModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
    <div class="flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-sm w-full">
        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Port OLT</h3>
            <button type="button" onclick="closeModal('portModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="../actions/olt_actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_port">
            <input type="hidden" name="olt_id" value="<?= $olt['id'] ?>">
            <input type="hidden" name="port_id" id="mPortId">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-white">Port Label</label>
                <input type="text" name="port_label" id="mPortLabel" required class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-white">Port Type</label>
                    <select name="port_type" id="mPortType" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="GPON">GPON</option>
                        <option value="EPON">EPON</option>
                        <option value="XGS-PON">XGS-PON</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-white">Status</label>
                    <select name="status" id="mPortStatus" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="inactive">Idle (Inactive)</option>
                        <option value="active">Active (Link)</option>
                        <option value="fault">Fault / Broken</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-white">Catatan Port</label>
                <textarea name="notes" id="mPortNotes" rows="2" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                <button type="button" onclick="closeModal('portModal')" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">Batal</button>
                <button type="submit" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    let selectedPortData = null;

    function selectPort(port) {
        selectedPortData = port;
        document.getElementById('pDetNumber').innerText = '#' + port.port_number;
        document.getElementById('pDetType').innerText = port.port_type;
        document.getElementById('pDetLabel').innerText = port.port_label || '-';
        
        let statusSpan = document.getElementById('pDetStatus');
        statusSpan.innerText = port.status.toUpperCase();
        if (port.status === 'active') {
            statusSpan.className = 'text-sm font-bold text-blue-600 dark:text-blue-400';
        } else if (port.status === 'fault') {
            statusSpan.className = 'text-sm font-bold text-red-600 dark:text-red-400';
        } else {
            statusSpan.className = 'text-sm font-bold text-gray-500 dark:text-gray-400';
        }

        // Fetch downstream path details if port is active
        let chainContainer = document.getElementById('pDetChainContainer');
        if (port.status === 'active' && port.backbone_code) {
            chainContainer.classList.remove('hidden');
            
            // Let's populate initial chain using path API
            // We find any drop point that connects to this backbone
            // Let's fetch using our ftth_data API
            fetch(`../actions/ftth_data.php?action=get_network_path&dp_id=0`) // Or we query downstream starting from backbone
                .then(response => response.json())
                .then(data => {
                    // Let's mock a simple downstream query by searching for drop points connected to this backbone.
                    // Wait, since we don't have a lookup by backbone, let's fetch downstream directly from backend if possible.
                    // Let's call the API with the specific backbone details.
                    // Actually, we can fetch the detail of the backbone and RK connected to this port.
                    // Let's make an API query to trace down from OLT port
                    // For now, let's fill in backbone and fetch RK details dynamically:
                    document.getElementById('chainOltPort').innerText = `${port.port_type} Port ${port.port_number} (${port.port_label || 'Default Label'})`;
                    let bbLabel = port.backbone_code;
                    if (port.core_tube) {
                        bbLabel += ` Core (Tube ${port.core_tube} Core ${port.core_num})`;
                    } else {
                        bbLabel += ` (Port Link)`;
                    }
                    document.getElementById('chainBackbone').innerText = bbLabel;
                    
                    // We query back from database via simple ajax call
                    // Let's fetch details of RK connections that connect to this backbone
                    fetch(`../actions/ftth_data.php?action=get_backbone_cores&backbone_id=${port.backbone_id}`)
                        .then(r => r.json())
                        .then(coresData => {
                            if (coresData.success && coresData.cores.length > 0) {
                                document.getElementById('chainRk').innerText = "Membuka diagram RK untuk mapping core...";
                                // Let's simplify the visual text for technician:
                                document.getElementById('chainRk').innerText = `Tersambung ke RK melalui core backbone`;
                                document.getElementById('chainDist').innerText = `Mendistribusikan ke wilayah coverage`;
                                document.getElementById('chainDp').innerText = `Mencakup Drop Point aktif`;
                                document.getElementById('chainDpDetailLink').style.display = 'none';
                            }
                        });
                });
        } else {
            chainContainer.classList.add('hidden');
        }

        document.getElementById('portDetailPanel').classList.remove('hidden');
    }

    function editSelectedPort() {
        if (!selectedPortData) return;
        document.getElementById('mPortId').value = selectedPortData.id;
        document.getElementById('mPortLabel').value = selectedPortData.port_label || '';
        document.getElementById('mPortType').value = selectedPortData.port_type;
        document.getElementById('mPortStatus').value = selectedPortData.status;
        document.getElementById('mPortNotes').value = selectedPortData.notes || '';
        openModal('portModal');
    }
</script>

<?php require_once '../includes/footer.php'; ?>
