<?php
require_once '../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FTTH Attenuation Simulator</title>
  
  <!-- CSS Design System & Components -->
  <link class="theme-style-sheet" rel="stylesheet" href="css/index.css">
  <link class="theme-style-sheet" rel="stylesheet" href="css/layout.css">
  <link class="theme-style-sheet" rel="stylesheet" href="css/sidebar.css">
  <link class="theme-style-sheet" rel="stylesheet" href="css/canvas.css">
  <link class="theme-style-sheet" rel="stylesheet" href="css/tooltip.css">
  <link class="theme-style-sheet" rel="stylesheet" href="css/modal.css">
</head>
<body>

  <div class="app-container">
    
    <!-- Top Header -->
    <header class="app-header">
      <div class="header-brand">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
          </svg>
        </div>
        <div class="brand-title">FTTH Simulator</div>
        <div class="badge badge-success" style="margin-left: 8px;">BETA</div>
      </div>
      
      <div class="header-controls">
        <div class="mode-selector">
          <label for="network-mode">Standard:</label>
          <select id="network-mode">
            <option value="GPON">GPON (Class B+/C+)</option>
            <option value="EPON">EPON (PX20+)</option>
          </select>
        </div>
        <div class="header-actions">
          <!-- Guide and Reset actions -->
          <button class="btn btn-secondary" id="btn-guide" title="Panduan & Glosarium">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Panduan
          </button>
          <button class="btn btn-secondary" id="btn-reset" title="Kosongkan Kanvas">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            Reset
          </button>

          <!-- Divider -->
          <div class="action-divider"></div>

          <!-- Primary Actions (Export PDF) & Utilities (Theme) -->
          <button class="btn btn-primary" id="btn-export" title="Export Laporan PDF">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export PDF
          </button>
          <button class="btn btn-icon btn-secondary" id="btn-theme-toggle" title="Ubah Tema" style="border-radius: var(--radius-full);">
            <!-- Sun Icon (for light mode toggled) -->
            <svg class="theme-icon-sun" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.72" x2="5.64" y2="18.3"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            <!-- Moon Icon (for dark mode toggled) -->
            <svg class="theme-icon-moon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
          </button>
        </div>
      </div>
    </header>

    <!-- Sidebar Equipment Palette -->
    <aside class="app-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-title">Equipment</h2>
      </div>
      <div class="palette-groups">
        
        <div class="palette-group">
          <div class="palette-group-title">
            <div class="status-dot success"></div> Active Devices
          </div>
          <div class="palette-items" id="palette-active">
            <!-- Populated by JS -->
          </div>
        </div>

        <div class="palette-group">
          <div class="palette-group-title">
            <div class="status-dot" style="background: var(--accent-indigo)"></div> Passive Devices
          </div>
          <div class="palette-items" id="palette-passive">
            <!-- Populated by JS -->
          </div>
        </div>

      </div>
    </aside>

    <!-- Main Canvas Area -->
    <main class="app-canvas-area" id="canvas-container">
      <div class="canvas-inner" id="canvas-inner">
        <svg id="connections-layer" class="connections-layer"></svg>
        <!-- Nodes injected here -->
      </div>
    </main>

    <!-- Bottom Status Bar -->
    <footer class="app-statusbar">
      <div id="status-bar-text" style="flex: 1;">
        <span class="text-muted">Drag equipment from the palette to start building your network.</span>
      </div>
      <div class="zoom-controls">
        <span class="text-muted text-xs">Zoom</span>
        <input type="range" id="zoom-slider" min="0.3" max="2" step="0.1" value="1">
        <span class="text-muted text-xs font-mono" id="zoom-value" style="width: 40px; text-align: right;">100%</span>
      </div>
    </footer>

  </div>

  <!-- Properties Modal Overlay -->
  <div class="modal-overlay" id="properties-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Properties</h3>
        <button class="btn-close" id="modal-close">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="modal-body">
        <div id="properties-form">
          <!-- Form fields injected by JS -->
        </div>
      </div>
    </div>
  </div>

  <!-- Info Modal Overlay -->
  <div class="modal-overlay" id="info-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <h3 class="modal-title" id="info-modal-title">Detail Estimasi Redaman</h3>
        <button class="btn-close" id="info-modal-close">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="modal-body">
        <div id="info-modal-content">
          <!-- Information injected by JS -->
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm Modal Overlay -->
  <div class="modal-overlay" id="confirm-overlay">
    <div class="modal-container" style="width: 360px;">
      <div class="modal-header">
        <h3 class="modal-title" id="confirm-title">Konfirmasi</h3>
        <button class="btn-close" id="confirm-close">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="modal-body">
        <p id="confirm-message" style="margin-bottom: var(--sp-4); line-height: 1.5; color: var(--text-secondary); font-size: var(--text-sm);"></p>
        <div style="display: flex; gap: var(--sp-3); justify-content: flex-end;">
          <button class="btn btn-secondary" id="confirm-btn-cancel" style="padding: var(--sp-2) var(--sp-4);">Batal</button>
          <button class="btn btn-danger" id="confirm-btn-yes" style="padding: var(--sp-2) var(--sp-4);">Hapus</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Guide Modal Overlay -->
  <div class="modal-overlay" id="guide-overlay">
    <div class="modal-container" style="width: 850px; max-width: 95vw;">
      <div class="modal-header">
        <h3 class="modal-title">Panduan FTTH & Glosarium</h3>
        <button class="btn-close" id="guide-modal-close">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="guide-layout">
          <!-- Sidebar Tabs Navigation -->
          <div class="guide-sidebar">
            <button class="guide-tab-btn active" data-target="guide-tab-tutorial">Panduan Simulasi</button>
            <button class="guide-tab-btn" data-target="guide-tab-features">Fitur &amp; Kontrol</button>
            <button class="guide-tab-btn" data-target="guide-tab-shortcuts">Interaksi &amp; Shortcut</button>
            <button class="guide-tab-btn" data-target="guide-tab-glossary">Glosarium FTTH</button>
            <button class="guide-tab-btn" data-target="guide-tab-reference">Referensi Redaman</button>
            <button class="guide-tab-btn" data-target="guide-tab-about">Tentang Sistem</button>
          </div>
          
          <!-- Content Panel -->
          <div class="guide-content-panel">
            
            <!-- Section 1: Tutorial -->
            <div class="guide-content-section active" id="guide-tab-tutorial">
              <h4>Cara Melakukan Simulasi Project FTTH</h4>
              <p>Simulator ini membantu Anda memetakan topologi jaringan FTTH (Fiber to the Home) secara <i>end-to-end</i> dari pusat provider (OLT) ke rumah pelanggan (ONT), serta mengestimasi redaman daya optik secara langsung (real-time).</p>
              
              <p><b>Langkah-langkah Simulasi:</b></p>
              <ol style="margin-left: var(--sp-4); margin-bottom: var(--sp-4); font-size: var(--text-sm); line-height: 1.6; color: var(--text-secondary);">
                <li><b>Tambah Perangkat:</b> Drag perangkat dari panel kiri (Active / Passive Devices) dan drop di kanvas workspace. Anda dapat menggeser posisi perangkat dengan men-drag kartu perangkat tersebut kapan saja.</li>
                <li><b>Hubungkan Perangkat:</b> Klik port <b>OUT</b> (berwarna hijau) di salah satu perangkat dan tarik garis (koneksi) menuju port <b>IN</b> (berwarna kuning) di perangkat tujuan. Hubungan kabel bersifat cerdas dan mendeteksi arah aliran sinyal optik secara dinamis.</li>
                <li><b>Atur Parameter:</b> Klik ikon gear <b>(Pengaturan)</b> pada kartu perangkat untuk menyesuaikan daya pancar (Tx Power), panjang kabel fiber optik, tipe konektor (SC/APC atau SC/UPC), rasio splitter, redaman adapter, dan ambang batas sensitivitas ONT.</li>
                <li><b>Analisis Redaman & Daya:</b> 
                  <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                    <li>Arahkan kursor (hover) ke perangkat untuk melihat redaman komponen, loss kumulatif, serta daya optik masuk (Input Power) dan keluar (Output Power).</li>
                    <li>Klik tombol <b>(i) Informasi Detail</b> di samping tombol pengaturan untuk menampilkan formulir kalkulasi terperinci (Loss Sheet) yang merinci secara matematis asal usul akumulasi redaman.</li>
                    <li>Arahkan kursor ke garis koneksi untuk melihat daya optik di titik kabel tersebut beserta tipe konektor yang terpasang.</li>
                  </ul>
                </li>
                <li><b>Evaluasi Status Keberhasilan:</b> Jalur koneksi dan ONT akan secara otomatis berubah warna berdasarkan budget daya optik: Hijau (Good), Kuning (Warning), dan Merah (Critical/No Signal).</li>
                <li><b>Export Blueprint:</b> Tekan tombol <b>Export PDF</b> untuk mengunduh blueprint diagram jaringan dan tabel kalkulasi budget link margin dalam tata letak satu halaman A4 Landscape.</li>
              </ol>
            </div>
            
            <!-- Section 2: Features -->
            <div class="guide-content-section" id="guide-tab-features">
              <h4>Fitur Utama & Kontrol Jaringan</h4>
              <p>Aplikasi ini dilengkapi dengan interaksi tingkat lanjut secara <i>end-to-end</i> untuk memudahkan analisis topologi FTTH Anda:</p>
              
              <div class="glossary-list">
                <div class="glossary-item">
                  <div class="glossary-term">Navigasi Workspace (Pan & Zoom Dinamis)</div>
                  <div class="glossary-definition">
                    - <b>Panning (Geser Kanvas)</b>: Klik dan tahan klik kiri mouse pada area kosong kanvas (atau garis grid latar belakang), lalu geser pointer mouse/trackpad Anda untuk berpindah tempat di area workspace tak terbatas secara leluasa.<br>
                    - <b>Zooming (Skala Kanvas)</b>: Gunakan scroll roda mouse Anda (mouse wheel) di atas area kanvas, pinch-to-zoom pada trackpad, atau geser slider zoom di status bar bagian bawah untuk mengatur skala canvas (30% hingga 200%). Fokus perbesaran/perkecilan akan secara cerdas mengikuti posisi koordinat kursor mouse Anda (zoom-to-pointer) dengan delta proporsional yang halus.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Seleksi Multi-Perangkat & Pemindahan Grup</div>
                  <div class="glossary-definition">
                    Fitur seleksi ganda memungkinkan pemindahan banyak perangkat secara bersamaan:
                    <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                      <li><b>Shift+Klik</b>: Tahan Shift lalu klik perangkat untuk menambah atau menghapusnya dari seleksi secara toggle (tanpa mengganggu seleksi perangkat lainnya).</li>
                      <li><b>Shift+Drag (Kotak Seleksi Marquee)</b>: Tahan Shift lalu klik dan seret di area kosong kanvas untuk menampilkan kotak seleksi transparan bergaris putus-putus. Semua perangkat yang tertutup/overlap dengan kotak ini akan otomatis terseleksi.</li>
                      <li><b>Pindah Grup</b>: Seret salah satu perangkat yang terseleksi untuk memindahkan seluruh grup bersamaan, menjaga posisi relatif dan alignment antar perangkat tetap presisi.</li>
                      <li><b>Hapus Grup</b>: Tekan Delete/Backspace saat beberapa perangkat terseleksi. Dialog konfirmasi kustom akan muncul menyebutkan jumlah perangkat yang akan dihapus.</li>
                      <li><b>Deseleksi</b>: Klik pada area kosong kanvas tanpa Shift untuk membatalkan seluruh seleksi.</li>
                    </ul>
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Menu Aksi Melayang (Selected Actions Overlay)</div>
                  <div class="glossary-definition">
                    Untuk menjaga kerapian area kerja dan mencegah teks nama perangkat terpotong, tombol aksi <b>(Info, Pengaturan, Hapus)</b> disembunyikan secara default. Cukup klik sekali pada kartu perangkat untuk menyeleksinya, dan panel tombol aksi yang lebih besar (ukuran ideal tombol 24px dengan ikon SVG 14px) akan muncul meluncur ke atas dari bagian bawah tubuh perangkat (body) dengan efek animasi pegas (<i>spring scale-up bounce</i>) yang dinamis.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Casing Realistis & LED Status Aktif (Live Hardware Indicators)</div>
                  <div class="glossary-definition">
                    Setiap perangkat dirancang menyerupai bentuk fisik perangkat aslinya:
                    <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                      <li><b>OLT</b>: Casing metal gelap rackmount 1U lengkap dengan bracket (telinga rack) dan sekrup pemasangan di kedua sisi. Dilengkapi LED <b>ACT</b> yang berkedip hijau dinamis ketika ada port output yang tersambung.</li>
                      <li><b>SFP Module</b>: Casing transceiver silver metalik presisi dengan deretan contact pin emas/tembaga dan tuas pengunci (latch lever) berwarna. Dirancang konsisten menggunakan warna silver/putih metalik baik di mode gelap maupun terang.</li>
                      <li><b>ONT</b>: Router rumahan putih/gelap dengan ventilasi udara atas dan antena ganda yang otomatis bergerak sedikit saat didekati kursor. Dilengkapi 4 LED aktif: <b>PWR</b> (hijau stabil), <b>PON</b> (hijau saat sinyal bagus, kuning saat marginal), <b>LOS</b> (kedip merah cepat saat putus/loss), dan <b>LAN</b> (kedip hijau saat mengirimkan data).</li>
                      <li><b>Splitter & ODP</b>: Desain casing kaset splitter dalam ruangan dan enclosure box ODP luar ruangan lengkap dengan visual silinder kunci pengunci.</li>
                      <li><b>Fiber Spool & Connector Adapter</b>: Spul gulungan kabel dengan flange oranye, serta adapter konektor dengan pewarnaan standar industri.</li>
                    </ul>
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Port Fisik SC Adapter & Core Glow</div>
                  <div class="glossary-definition">
                    Port koneksi digambarkan berbentuk kotak adapter persegi menyerupai socket SC fiber asli. Inti bagian dalam port (core) akan memancarkan cahaya hijau/teal terang jika kabel berhasil terhubung (port active). Adapter menggunakan standar warna standar industri: biru tebal di sisi kiri untuk port input <b>SC/UPC</b> (flange datar) dan hijau tebal di sisi kanan untuk port output <b>SC/APC</b> (flange miring) pada perangkat konektor.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Validasi Koneksi Port & Kapasitas Perangkat</div>
                  <div class="glossary-definition">
                    Sistem menerapkan aturan validasi kapasitas koneksi yang realistis berdasarkan tipe perangkat:
                    <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                      <li><b>Splitter & ODP</b>: Jumlah koneksi keluar dibatasi sesuai rasio splitter yang dipilih (mis. 1:2 = maks. 2, 1:8 = maks. 8 koneksi keluar).</li>
                      <li><b>OLT</b>: Bebas memasang banyak SFP Module — tidak ada batasan koneksi keluar (exception unik).</li>
                      <li><b>Perangkat Lainnya</b> (SFP, Fiber Cable, Connector, ONT): Dibatasi maksimal 1 koneksi keluar per perangkat.</li>
                      <li><b>Proteksi Rasio</b>: Merubah rasio Splitter/ODP ke kapasitas lebih kecil ditolak jika jumlah koneksi aktif melebihi batas baru; dropdown otomatis di-revert.</li>
                    </ul>
                    Pelanggaran validasi ditampilkan melalui notifikasi <b>toast</b> Glassmorphism yang meluncur dari pojok kanan atas layar.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Fitur Hapus Perangkat & Koneksi (Dinamis & Aman)</div>
                  <div class="glossary-definition">
                    - <b>Hapus Perangkat (Equipment)</b>: Klik sekali pada kartu perangkat untuk menampilkan panel tombol aksi melayang di bagian bawah tubuh perangkat, lalu klik tombol tempat sampah merah <b>(Hapus)</b>, menekan tombol "Hapus Perangkat" di panel properties, atau tekan tombol <b>Delete/Backspace</b> di keyboard saat perangkat terseleksi. Dialog konfirmasi kustom Glassmorphism akan muncul terlebih dahulu.<br>
                    - <b>Hapus Koneksi (Kabel)</b>: Klik ganda (double-click) langsung pada garis koneksi untuk memutuskannya secara instan, atau klik sekali pada garis koneksi (hingga berwarna biru menyala) lalu tekan tombol <b>Delete/Backspace</b> di keyboard. Koneksi visual dan relasi daya logis akan langsung terputus, memicu kalkulasi ulang status redaman pada perangkat hilir tanpa perlu me-refresh halaman.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Reset Workspace Komprehensif</div>
                  <div class="glossary-definition">
                    Gunakan tombol <b>Reset</b> di header menu atas untuk langsung menghapus seluruh perangkat dan jalur koneksi di kanvas. Fitur ini juga secara otomatis mengembalikan posisi koordinat geser (pan) ke koordinat awal (0, 0), mengembalikan tingkat perbesaran (zoom) ke skala normal 100%, dan menghapus data <i>autosave</i> dari localStorage setelah dikonfirmasi via dialog popup konfirmasi kustom.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Lembar Rincian Kalkulasi Redaman (Loss Sheet Modal)</div>
                  <div class="glossary-definition">
                    Fitur tombol <b>(i) Informasi</b> di samping tombol properti Gear membuka lembar rincian kalkulasi matematika redaman. Ini merinci seluruh penyumbang redaman (seperti redaman kabel per kilometer, redaman splitter berdasarkan rasio pembagian daya, connector loss, dan splicing loss) secara komprehensif untuk mendidik dan membimbing pengguna.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Kalkulasi Redaman Real-Time & Threshold ONT</div>
                  <div class="glossary-definition">
                    Setiap kali Anda menambah perangkat, menyambung kabel, atau merubah konfigurasi parameter, sistem langsung melakukan kalkulasi <i>propagation loss</i> dari OLT ke ONT. ONT akan mengevaluasi daya terima optik (Rx Power) terhadap ambang batas sensitivitasnya:
                    <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                      <li><b>Status Layak (Good)</b>: Berwarna hijau, Rx Power >= -24.00 dBm. Sinyal optik sangat prima dan stabil.</li>
                      <li><b>Status Peringatan (Warning)</b>: Berwarna kuning, Rx Power berada di antara -24.01 dBm hingga -27.00 dBm. Koneksi terhubung namun rentan fluktuasi.</li>
                      <li><b>Status Kritis (Critical/Loss)</b>: Berwarna merah, Rx Power < -27.00 dBm. Sinyal terlalu lemah atau di bawah sensitivitas ONT sehingga layanan internet terputus.</li>
                    </ul>
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Tema Light & Dark Mode Terintegrasi</div>
                  <div class="glossary-definition">
                    Ubah tema simulator secara instan menggunakan tombol matahari/bulan di pojok kanan atas. CSS Custom Variables secara mulus mengubah seluruh visual sistem termasuk bayangan grid kanvas, warna kartu perangkat, dan animasi dot konektor laser agar nyaman di mata dalam berbagai kondisi pencahayaan. Casing metalik perangkat beradaptasi dinamis mengikuti mode gelap/terang, kecuali modul SFP yang dirancang konsisten tetap silver/putih metalik. Kartu perangkat di sidebar menu juga otomatis mengikuti warna background/border tema yang aktif.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Professional Landscape PDF Report</div>
                  <div class="glossary-definition">
                    Tombol <b>Export PDF</b> (tombol utama berwarna teal di header) mengonversi kanvas topologi simulasi Anda menjadi cetakan laporan PDF A4 Landscape satu halaman. Laporan ini tertata secara profesional, menyajikan diagram jaringan (termasuk garis koneksi SVG) beresolusi tinggi di bagian atas, legenda warna sinyal di tengah, serta tabel terperinci perhitungan link budget (Equipment Loss & Power Budget) di bagian bawah yang siap dipresentasikan.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Status Bar Interaktif & Ikon SVG</div>
                  <div class="glossary-definition">
                    Status bar di bagian bawah menampilkan ringkasan topologi (jumlah perangkat, koneksi, Tx Power, hasil link budget per ONT) menggunakan ikon SVG vektor tajam yang konsisten di semua platform. Status bar mendukung <b>scroll horizontal</b> menggunakan roda mouse (vertikal di-translate otomatis menjadi horizontal), sehingga seluruh informasi ONT dapat diakses meski terpotong oleh slider zoom di sisi kanan.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Autosave & Autoload (localStorage)</div>
                  <div class="glossary-definition">
                    Seluruh state simulasi (posisi perangkat, koneksi, parameter konfigurasi, koordinat pan, dan tingkat zoom) otomatis tersimpan ke <b>localStorage</b> browser setiap kali Anda memindahkan perangkat, menambah/menghapus koneksi, mengubah properti, atau melakukan pan/zoom. Saat halaman dibuka kembali, topologi terakhir akan otomatis dimuat ulang secara penuh.
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Section 3: Shortcuts & Interactions -->
            <div class="guide-content-section" id="guide-tab-shortcuts">
              <h4>Interaksi Mouse & Keyboard Shortcut</h4>
              <p>Daftar lengkap semua interaksi mouse dan pintasan keyboard yang tersedia di simulator ini:</p>
              
              <table class="guide-table">
                <thead>
                  <tr>
                    <th>Aksi</th>
                    <th>Input</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Tambah Perangkat</td>
                    <td><b>Drag & Drop</b></td>
                    <td>Drag dari panel perangkat kiri ke area kanvas workspace</td>
                  </tr>
                  <tr>
                    <td>Pindah Perangkat</td>
                    <td><b>Drag</b> di kartu</td>
                    <td>Klik dan tahan kartu perangkat di kanvas, geser ke posisi baru</td>
                  </tr>
                  <tr>
                    <td>Seleksi Perangkat</td>
                    <td><b>Klik</b> pada kartu</td>
                    <td>Klik sekali pada perangkat untuk menyeleksinya (border menyala, muncul menu aksi melayang)</td>
                  </tr>
                  <tr>
                    <td>Multi-Seleksi (Toggle)</td>
                    <td><b>Shift + Klik</b></td>
                    <td>Tahan Shift lalu klik perangkat untuk menambah/menghapus dari seleksi grup</td>
                  </tr>
                  <tr>
                    <td>Kotak Seleksi (Marquee)</td>
                    <td><b>Shift + Drag</b> area kosong</td>
                    <td>Tahan Shift lalu seret di area kosong untuk menampilkan kotak seleksi transparan; semua perangkat yang tertutup otomatis terseleksi</td>
                  </tr>
                  <tr>
                    <td>Pindah Grup Perangkat</td>
                    <td><b>Drag</b> perangkat terseleksi</td>
                    <td>Seret salah satu perangkat dalam grup seleksi untuk memindahkan seluruh grup bersamaan</td>
                  </tr>
                  <tr>
                    <td>Buat Koneksi Kabel</td>
                    <td><b>Klik OUT → IN</b></td>
                    <td>Klik port OUT (hijau) lalu klik port IN (kuning) di perangkat tujuan</td>
                  </tr>
                  <tr>
                    <td>Hapus Perangkat</td>
                    <td><b>Delete / Backspace</b></td>
                    <td>Tekan saat perangkat terseleksi; atau klik ikon Hapus di panel aksi melayang di bagian bawah kartu</td>
                  </tr>
                  <tr>
                    <td>Hapus Multi-Perangkat</td>
                    <td><b>Delete / Backspace</b></td>
                    <td>Tekan saat beberapa perangkat terseleksi; dialog konfirmasi muncul menyebutkan jumlah perangkat</td>
                  </tr>
                  <tr>
                    <td>Hapus Koneksi</td>
                    <td><b>Double-Click</b> pada garis</td>
                    <td>Klik ganda langsung pada garis koneksi untuk memutuskan kabel</td>
                  </tr>
                  <tr>
                    <td>Hapus Koneksi (Alt)</td>
                    <td><b>Klik + Delete</b></td>
                    <td>Klik sekali pada garis (menyala biru), lalu tekan Delete/Backspace</td>
                  </tr>
                  <tr>
                    <td>Geser Kanvas (Pan)</td>
                    <td><b>Drag</b> area kosong</td>
                    <td>Klik dan tahan di area kosong/grid, lalu geser mouse</td>
                  </tr>
                  <tr>
                    <td>Zoom In / Out</td>
                    <td><b>Scroll Wheel</b></td>
                    <td>Gulir roda mouse ke atas (zoom in) atau ke bawah (zoom out); zoom mengikuti posisi kursor</td>
                  </tr>
                  <tr>
                    <td>Scroll Status Bar</td>
                    <td><b>Scroll Wheel</b> di status bar</td>
                    <td>Gulir roda mouse (vertikal) di atas status bar akan otomatis di-translate menjadi scroll horizontal</td>
                  </tr>
                  <tr>
                    <td>Buka Properties</td>
                    <td>Klik <b>Gear</b> / <b>Double-Click</b></td>
                    <td>Klik ikon Gear pada panel aksi melayang di bawah kartu saat terpilih; atau klik ganda langsung pada kartu</td>
                  </tr>
                  <tr>
                    <td>Lihat Detail Redaman</td>
                    <td>Klik ikon <b>Info</b></td>
                    <td>Klik ikon Info pada panel aksi melayang di bawah kartu saat terpilih untuk membuka Loss Sheet</td>
                  </tr>
                  <tr>
                    <td>Lihat Tooltip Cepat</td>
                    <td><b>Hover</b> perangkat</td>
                    <td>Arahkan kursor untuk melihat ringkasan daya & redaman (auto tooltip dengan ikon SVG)</td>
                  </tr>
                  <tr>
                    <td>Ganti Tema</td>
                    <td>Klik ikon <b>Tema</b> (bulan/matahari)</td>
                    <td>Toggle antara mode Light dan Dark secara instan</td>
                  </tr>
                  <tr>
                    <td>Reset Seluruh Kanvas</td>
                    <td>Klik tombol <b>Reset</b></td>
                    <td>Hapus semua perangkat, koneksi, data autosave, reset pan & zoom ke default</td>
                  </tr>
                  <tr>
                    <td>Export PDF</td>
                    <td>Klik tombol <b>Export PDF</b></td>
                    <td>Unduh blueprint diagram (dengan garis koneksi SVG) & tabel redaman dalam format A4 Landscape</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Section 4: Glossary -->
            <div class="guide-content-section" id="guide-tab-glossary">
              <h4>Glosarium FTTH</h4>
              <div class="glossary-list">
                <div class="glossary-item">
                  <div class="glossary-term">OLT (Optical Line Terminal)</div>
                  <div class="glossary-definition">Perangkat aktif di kantor pusat provider (Central Office) sebagai permulaan transmisi sinyal optik downstream ke pelanggan. Dilengkapi casing besi rackmount 1U dan LED <b>ACT</b> yang berkedip hijau saat port output tersambung.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">SFP Module (Transceiver)</div>
                  <div class="glossary-definition">Modul pemancar-penerima optik yang ditancapkan pada OLT untuk menyalurkan cahaya laser. Berbentuk transceiver metalik perak (silver) berkontak tembaga dengan pengait lever pengunci. Visual casing-nya dirancang konsisten putih/silver metalik di mode terang maupun gelap.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Passive Splitter</div>
                  <div class="glossary-definition">Komponen pasif pembagi daya sinyal optik (misal 1:2, 1:4, hingga 1:64) dari satu core serat menjadi beberapa core untuk disebarkan ke pelanggan. Memiliki redaman intrinsik yang cukup besar sesuai rasio pembagiannya.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">ODP (Optical Distribution Point)</div>
                  <div class="glossary-definition">Kotak pelindung outdoor (biasanya dipasang di tiang listrik atau dinding) yang berisi splitter dan adapter konektor tempat drop core pelanggan disambungkan. Dilengkapi visual slot kunci pengunci.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Kabel Fiber (Feeder / Distribusi)</div>
                  <div class="glossary-definition">Kabel optik Single Mode (SM) tipe G.652 yang membentang dari OLT ke ODP. Mengalami redaman linear sebesar 0.25 dB/km pada panjang gelombang downstream 1490nm.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Kabel Drop (Drop Core)</div>
                  <div class="glossary-definition">Kabel serat optik berdiameter kecil yang ditarik dari ODP langsung menuju rumah pelanggan (ONT), umumnya memiliki jarak pendek di bawah 500 meter.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Adapter & Konektor SC</div>
                  <div class="glossary-definition">Titik terminasi/penyambungan fisik kabel optik. Tipe <b>SC/UPC</b> berwarna biru tebal memiliki standar loss 0.50 dB (tipe flange datar), sedangkan <b>SC/APC</b> berwarna hijau tebal memiliki standar loss lebih rendah yaitu 0.30 dB karena ujungnya dipoles miring 8 derajat untuk meminimalkan pantulan balik.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">ONT (Optical Network Terminal) / ONU</div>
                  <div class="glossary-definition">Perangkat akhir di rumah pelanggan yang mengubah cahaya optik menjadi sinyal data Ethernet/Wi-Fi. Memiliki sensitivitas Rx batas minimal -27 dBm dan dilengkapi 4 lampu LED (PWR, PON, LOS, LAN) untuk pemantauan status koneksi secara real-time.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">LED Indikator Status (Live LEDs)</div>
                  <div class="glossary-definition">Lampu status interaktif pada perangkat yang merespons kalkulasi redaman simulasi secara real-time. Lampu <b>ACT</b> (pada OLT) dan <b>PON / LAN / LOS</b> (pada ONT) menyala atau berkedip sesuai kondisi daya terima (Rx Power) dan konektivitas kabel.</div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Panel Aksi Melayang (Selected Actions Overlay)</div>
                  <div class="glossary-definition">Menu overlay melayang kontekstual yang muncul meluncur dengan animasi pegas di atas/bawah perangkat saat perangkat dipilih. Berisi tombol pintas cepat seperti Info Detail (ℹ️), Pengaturan Properti (⚙️), dan Hapus Perangkat (🗑️).</div>
                </div>
              </div>
            </div>
            
            <!-- Section 4: Reference -->
            <div class="guide-content-section" id="guide-tab-reference">
              <h4>Standar Parameter Redaman (ITU-T & IEEE)</h4>
              <p>Simulator ini menghitung redaman secara real-time berdasarkan nilai standar industri berikut:</p>
              
              <table class="guide-table">
                <thead>
                  <tr>
                    <th>Komponen Jaringan</th>
                    <th>Tipe / Parameter</th>
                    <th>Standar Loss (dB)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Kabel Fiber (1490nm)</td>
                    <td>Per Kilometer (km)</td>
                    <td>0.25 dB / km</td>
                  </tr>
                  <tr>
                    <td>Kabel Fiber (1310nm)</td>
                    <td>Per Kilometer (km)</td>
                    <td>0.35 dB / km</td>
                  </tr>
                  <tr>
                    <td>Splice (Sambungan Fusion)</td>
                    <td>Per Joint / Titik Las</td>
                    <td>0.10 dB</td>
                  </tr>
                  <tr>
                    <td>Konektor SC/APC</td>
                    <td>Warna Hijau (Miring)</td>
                    <td>0.30 dB</td>
                  </tr>
                  <tr>
                    <td>Konektor SC/UPC</td>
                    <td>Warna Biru (Datar)</td>
                    <td>0.50 dB</td>
                  </tr>
                  <tr>
                    <td rowspan="6">Passive Splitter Loss</td>
                    <td>Splitter 1:2</td>
                    <td>3.50 dB</td>
                  </tr>
                  <tr>
                    <td>Splitter 1:4</td>
                    <td>7.00 dB</td>
                  </tr>
                  <tr>
                    <td>Splitter 1:8</td>
                    <td>10.50 dB</td>
                  </tr>
                  <tr>
                    <td>Splitter 1:16</td>
                    <td>14.00 dB</td>
                  </tr>
                  <tr>
                    <td>Splitter 1:32</td>
                    <td>17.50 dB</td>
                  </tr>
                  <tr>
                    <td>Splitter 1:64</td>
                    <td>21.00 dB</td>
                  </tr>
                  <tr>
                    <td>Sensitivitas ONT (GPON)</td>
                    <td>Batas Sinyal Minimum</td>
                    <td>-27.00 dBm</td>
                  </tr>
                </tbody>
              </table>
              <p style="font-size: 11px; color: var(--text-muted);"><i>Catatan: Budget Link Margin yang ideal adalah &gt; 3 dB di atas sensitivitas ONT untuk menjamin stabilitas koneksi terhadap fluktuasi cuaca atau tekukan kabel (bending).</i></p>
            </div>
            
            <!-- Section 6: About System -->
            <div class="guide-content-section" id="guide-tab-about">
              <h4>Tentang FTTH Attenuation Simulator</h4>
              <p>Simulator ini adalah aplikasi web interaktif berbasis <b>HTML5, CSS3, dan JavaScript murni (Vanilla JS)</b> yang dirancang untuk membantu teknisi, mahasiswa, dan perencana jaringan telekomunikasi memahami dan mensimulasikan topologi jaringan FTTH secara visual dan edukatif.</p>
              
              <div class="glossary-list">
                <div class="glossary-item">
                  <div class="glossary-term">Arsitektur Sistem</div>
                  <div class="glossary-definition">
                    Aplikasi ini berjalan sepenuhnya di sisi klien (client-side) tanpa memerlukan server backend atau database. Seluruh logika kalkulasi redaman, rendering koneksi SVG, dan interaksi kanvas ditangani langsung oleh browser menggunakan modul JavaScript terpisah:
                    <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                      <li><b>equipment-data.js</b> — Definisi parameter perangkat FTTH (source-of-truth)</li>
                      <li><b>calculator.js</b> — Engine kalkulasi redaman & power budget (recursive tree traversal)</li>
                      <li><b>canvas.js</b> — Pan, zoom, seleksi multi-node, marquee, dan manajemen kanvas workspace</li>
                      <li><b>connections.js</b> — Rendering koneksi SVG Bezier & animasi laser dot</li>
                      <li><b>drag-drop.js</b> — Drag-and-drop dari palette ke kanvas & pemindahan grup perangkat</li>
                      <li><b>tooltip.js</b> — Tooltip otomatis saat hover perangkat (ikon SVG vektor)</li>
                      <li><b>app.js</b> — Orkestrator utama, modal, tema, autosave/autoload, export PDF, toast, dan event binding</li>
                    </ul>
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Desain & UI/UX</div>
                  <div class="glossary-definition">
                    Antarmuka menggunakan pendekatan <b>Glassmorphism</b> modern dengan efek kaca transparan (backdrop-filter), CSS Custom Properties (variabel warna), transisi halus, dan mode gelap/terang terintegrasi. Grid kanvas menampilkan pola dot-grid yang adaptif terhadap tema aktif. Seluruh ikon menggunakan SVG vektor inline (tidak ada emoji) untuk konsistensi rendering di semua platform dan resolusi. Header menu di-organisir dengan divider visual dan tombol utama (Export PDF) berwarna teal sebagai Call-to-Action primer.
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Standar Referensi</div>
                  <div class="glossary-definition">
                    Seluruh parameter redaman dan kalkulasi mengacu pada standar industri telekomunikasi internasional:
                    <ul style="margin-left: var(--sp-4); margin-top: var(--sp-1);">
                      <li><b>ITU-T G.984</b> — Gigabit-capable Passive Optical Networks (GPON)</li>
                      <li><b>ITU-T G.652</b> — Characteristics of a single-mode optical fibre cable</li>
                      <li><b>IEEE 802.3ah</b> — Ethernet in the First Mile (EPON)</li>
                    </ul>
                  </div>
                </div>
                <div class="glossary-item">
                  <div class="glossary-term">Penyimpanan & Privasi</div>
                  <div class="glossary-definition">
                    Tidak ada data yang dikirim ke server manapun. Seluruh simulasi berjalan secara lokal di perangkat browser Anda. Data topologi (posisi perangkat, koneksi, parameter, zoom, dan pan) disimpan secara otomatis di <b>localStorage</b> browser dan dimuat kembali saat halaman dibuka ulang. Anda dapat menghapus seluruh data tersimpan menggunakan tombol <b>Reset</b> di header.
                  </div>
                </div>
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Print Report Area (Hidden normally) -->
  <div id="print-area" style="display: none;">
    <div class="print-header">
      <h1>FTTH NETWORK SIMULATION BLUEPRINT</h1>
      <div class="print-meta-grid">
        <div><strong>Standard Reference:</strong> ITU-T G.984 (GPON) / IEEE 802.3ah (EPON) & G.652 SM Fiber</div>
        <div id="print-date"></div>
      </div>
    </div>
    
    <div class="print-section">
      <h2>1. Topology & Connection Map</h2>
      <div id="print-diagram-container"></div>
      
      <div class="print-legend">
        <div class="legend-title">Legend:</div>
        <div class="legend-items">
          <div class="legend-item"><span class="legend-color line-good"></span> Good Signal</div>
          <div class="legend-item"><span class="legend-color line-warning"></span> Warning / Marginal</div>
          <div class="legend-item"><span class="legend-color line-critical"></span> Critical / Under threshold</div>
          <div class="legend-item"><span class="legend-color node-active"></span> Active Component</div>
          <div class="legend-item"><span class="legend-color node-passive"></span> Passive Component</div>
        </div>
      </div>
    </div>
    
    <div class="print-section">
      <h2>2. Equipment Loss & Power Budget</h2>
      <div id="print-table-container"></div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="js/equipment-data.js"></script>
  <script src="js/calculator.js"></script>
  <script src="js/canvas.js"></script>
  <script src="js/connections.js"></script>
  <script src="js/drag-drop.js"></script>
  <script src="js/tooltip.js"></script>
  <script src="js/app.js"></script>
</body>
</html>
