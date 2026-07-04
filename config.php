<?php
// config.php - Main Configuration File

// Security: Prevent Direct Access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden: Direct access to this file is not allowed.');
}

// Perform automated cleanup of old sessions
// Probability 1/100
if (rand(1, 100) === 1) {
    session_start(); 
    session_gc();
    session_write_close();
}

// Load Environment Variables
require_once __DIR__ . '/includes/env_loader.php';
EnvLoader::load(__DIR__);

// --- Security Headers ---
// Prevent Clickjacking
header("X-Frame-Options: DENY");
// XSS Protection (Browser-level)
header("X-XSS-Protection: 1; mode=block");
// Prevent MIME sniffing
header("X-Content-Type-Options: nosniff");
// HSTS (Force HTTPS) - 1 Year Cache
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
// Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");
// Content Security Policy
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https:;");
// Permissions Policy
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// --- Error Reporting ---
if (getenv('APP_ENV') === 'production') {
    // Hide errors in production
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    // Ensure we log errors
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error.log');
} else {
    // Show errors in dev
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// --- Secure Session Settings ---
// Prevent JavaScript access to session cookie
ini_set('session.cookie_httponly', 1);
// Prevent Session Fixation
ini_set('session.use_strict_mode', 1);
// SameSite Policy
ini_set('session.cookie_samesite', 'Strict');

// Force Secure Cookie if HTTPS
$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if ($is_https) {
    ini_set('session.cookie_secure', 1);
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Session Timeout Logic (30 Minutes) ---
$timeout_duration = 1800; // 30 minutes in seconds

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        // Session expired
        session_unset();     // unset $_SESSION variable for the run-time 
        session_destroy();   // destroy session data in storage
        
        // Restart session to flash message
        session_start();
        set_flash_message('warning', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.');
        
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity timestamp


// --- Database Credentials ---
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_socket_enabled = filter_var(getenv('DB_UNIX_SOCKET_ENABLED'), FILTER_VALIDATE_BOOLEAN);
$db_socket = getenv('DB_UNIX_SOCKET');

// For backwards compatibility (in case files use these constants directly)
// Ideally, we should refactor everything to use $pdo or helper functions,
// but defining them here prevents immediate breakage.
if (!defined('DB_HOST')) define('DB_HOST', $db_host);
if (!defined('DB_PORT')) define('DB_PORT', $db_port);
if (!defined('DB_USER')) define('DB_USER', $db_user);
if (!defined('DB_PASS')) define('DB_PASS', $db_pass);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);

// --- Database Connection ---
try {
    if ($db_socket_enabled && !empty($db_socket)) {
        $dsn = "mysql:unix_socket=" . $db_socket . ";dbname=" . $db_name . ";charset=utf8mb4";
    } else {
        $dsn = "mysql:host=" . $db_host . ";port=" . $db_port . ";dbname=" . $db_name . ";charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Disable emulation of prepared statements for better security against SQLi
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Don't echo $e->getMessage() directly in production to avoid leaking sensitive info
    if (getenv('APP_ENV') === 'production') {
        error_log("Database Connection Failed: " . $e->getMessage());
        die("System Error: Unable to connect to database. Please try again later.");
    } else {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

// --- Load Settings from DB ---
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings_db = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settings_db = [];
    error_log("Failed to load settings: " . $e->getMessage());
}

// --- Application Constants ---
define('APP_NAME', $settings_db['app_name'] ?? getenv('APP_NAME') ?? 'PCA Net');
define('APP_ICON', $settings_db['app_icon'] ?? 'default_icon.png');
define('COMPANY_NAME', $settings_db['company_name'] ?? 'My ISP Company');

// Dynamic BASE_URL
// Dynamic BASE_URL Detection
if (php_sapi_name() === 'cli') {
    // In CLI mode (e.g., cron jobs), HTTP_HOST and DOCUMENT_ROOT are unavailable.
    // Fallback to APP_URL from .env or default to localhost.
    $envUrl = getenv('APP_URL');
    define('BASE_URL', !empty($envUrl) ? rtrim($envUrl, '/') : 'http://localhost');
} else {
    // Web Mode
    $protocol = $is_https ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    
    // Calculate relative path from web root to this project root
    $projectDir = str_replace('\\', '/', __DIR__);
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    
    // Check if project is inside document root
    if (strpos($projectDir, $docRoot) === 0) {
        $relativePath = str_replace($docRoot, '', $projectDir);
        $relativePath = rtrim($relativePath, '/'); 
    } else {
        // Fallback or Symlink case: assume root or handle manually
        $relativePath = ''; 
    }

    // Web Mode: Always use dynamic detection to match the current server name/IP
    // ignoring APP_URL from .env to prevent unwanted redirects (e.g. to localhost)
    define('BASE_URL', $protocol . '://' . $host . $relativePath);
}

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('INVOICE_SECRET', 'SuperSecretKeyChangeMeInProd'); // Consider moving this to .env too

// Encryption Key
$env_key = getenv('ENCRYPTION_KEY');
if (!$env_key) {
    // Fail safe if key is missing
    die("Configuration Error: ENCRYPTION_KEY is missing.");
}
define('ENCRYPTION_KEY', $env_key);


// --- Helper Functions ---

/**
 * Encrypt sensitive data using AES-256-CBC
 * @param string $plaintext The data to encrypt
 * @return string Base64 encoded encrypted string (includes IV)
 */
function encrypt_data($plaintext) {
    if (empty($plaintext)) return '';
    
    $cipher = 'aes-256-cbc';
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    $encrypted = openssl_encrypt($plaintext, $cipher, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    
    // Prepend IV to encrypted data and encode
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data using AES-256-CBC
 * @param string $encryptedData Base64 encoded encrypted string
 * @return string Decrypted plaintext
 */
function decrypt_data($encryptedData) {
    if (empty($encryptedData)) return '';
    
    $cipher = 'aes-256-cbc';
    $ivLength = openssl_cipher_iv_length($cipher);
    
    // Decode and extract IV
    $data = base64_decode($encryptedData);
    if ($data === false || strlen($data) <= $ivLength) {
        // Return as-is if not properly encrypted (for backwards compatibility)
        return $encryptedData;
    }
    
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    $decrypted = openssl_decrypt($encrypted, $cipher, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    
    // Return decrypted or original if decryption fails (backwards compatibility)
    return $decrypted !== false ? $decrypted : $encryptedData;
}

/**
 * Sanitize User Input
 */
function clean_input($data) {
    // Trim whitespace
    $data = trim($data);
    // Remove backslashes
    $data = stripslashes($data);
    // Convert special characters to HTML entities
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Format Currency (IDR)
 */
function format_rupiah($number) {
    return "Rp " . number_format($number, 0, ',', '.');
}

/**
 * Check if user is logged in
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

/**
 * Check for specific role access
 */
function require_role($allowed_roles) {
    require_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header('HTTP/1.0 403 Forbidden');
        die("Access Denied: You do not have permission to view this page.");
    }
}

/**
 * CSRF Protection: Generate Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Protection: Verify Token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header('HTTP/1.0 403 Forbidden');
        die("CSRF Validation Failed");
    }
}

/**
 * Flash Message Helper
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Pagination Helper
function render_pagination($total_pages, $current_page, $url_params = []) {
    if ($total_pages <= 1) return '';
    
    $query = $_GET;
    unset($query['page']); // Remove current page from query
    $base_url = '?' . http_build_query($query);
    
    $html = '<nav class="flex items-center gap-x-1" aria-label="Pagination">';
    
    // Prev
    $prev_disabled = $current_page <= 1 ? 'disabled pointer-events-none opacity-50' : '';
    $prev_url = $base_url . '&page=' . ($current_page - 1);
    $html .= '<a class="min-h-[38px] min-w-[38px] py-2 px-2.5 inline-flex justify-center items-center gap-x-2 text-sm rounded-lg text-gray-800 dark:text-gray-200 hover:bg-[#F8FAFC] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F8FAFC] dark:focus:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none ' . $prev_disabled . '" href="' . $prev_url . '">
                <svg class="flex-shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                <span aria-hidden="true" class="sr-only">Previous</span>
              </a>';

    // Numbers (Sliding Window)
    $delta = 2; // Number of pages adjacent to current page
    $range = [];
    $rangeWithDots = [];

    $l = null;

    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $delta && $i <= $current_page + $delta)) {
            $range[] = $i;
        }
    }

    foreach ($range as $i) {
        if ($l) {
            if ($i - $l === 2) {
                $rangeWithDots[] = ($l + 1);
            } elseif ($i - $l > 2) {
                 $rangeWithDots[] = '...';
            }
        }
        $rangeWithDots[] = $i;
        $l = $i;
    }

    foreach ($rangeWithDots as $i) {
        if ($i === '...') {
             $html .= '<div class="min-h-[38px] min-w-[38px] flex justify-center items-center text-gray-500 dark:text-gray-400 py-2 px-3 text-sm">...</div>';
        } else {
            $active_class = $i == $current_page ? 'bg-gray-200 text-gray-800 dark:bg-slate-700 dark:text-white focus:bg-gray-200 dark:focus:bg-slate-700' : 'text-gray-800 dark:text-gray-200 hover:bg-[#F8FAFC] dark:hover:bg-slate-700 focus:bg-[#F8FAFC] dark:focus:bg-slate-700';
            $url = $base_url . '&page=' . $i;
            $html .= '<a class="min-h-[38px] min-w-[38px] flex justify-center items-center text-gray-800 dark:text-gray-200 py-2 px-3 text-sm rounded-lg focus:outline-none ' . $active_class . '" href="' . $url . '">' . $i . '</a>';
        }
    }

    // Next
    $next_disabled = $current_page >= $total_pages ? 'disabled pointer-events-none opacity-50' : '';
    $next_url = $base_url . '&page=' . ($current_page + 1);
    $html .= '<a class="min-h-[38px] min-w-[38px] py-2 px-2.5 inline-flex justify-center items-center gap-x-2 text-sm rounded-lg text-gray-800 dark:text-gray-200 hover:bg-[#F8FAFC] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F8FAFC] dark:focus:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none ' . $next_disabled . '" href="' . $next_url . '">
                <span aria-hidden="true" class="sr-only">Next</span>
                <svg class="flex-shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
              </a>';
    
    $html .= '</nav>';
    return $html;
}

