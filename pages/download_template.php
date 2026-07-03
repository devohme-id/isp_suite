<?php
require_once '../config.php';
require_once '../includes/SimpleXLSXGen.php';

require_login();

$headers = [
    'Kode Pelanggan (Opsional)',
    'Nama Lengkap', 
    'Email', 
    'No. HP', 
    'Alamat Lengkap', 
    'Latitude', 
    'Longitude',
    'ID Paket', 
    'Tanggal Instalasi (YYYY-MM-DD)', 
    'Tanggal Jatuh Tempo (1-28)',
    'Status (active/suspended/terminated)'
];

// Dummy Data
$data = [
    $headers,
    ['', 'Budi Santoso', 'budi@example.com', '08123456789', 'Jl. Merdeka No. 10, Jakarta', '-6.200000', '106.816666', 1, date('Y-m-d'), 10, 'active'],
    ['CST-999', 'Siti Aminah', 'siti@example.com', '08987654321', 'Jl. Sudirman No. 5, Bandung', '-6.917464', '107.619123', 2, date('Y-m-d'), 20, 'active']
];

$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs('template_import_pelanggan.xlsx');
exit;
