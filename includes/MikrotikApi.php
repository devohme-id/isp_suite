<?php
/**
 * MikroTik RouterOS API-SSL Client
 * 
 * Connects to MikroTik RouterOS via API-SSL (port 8729)
 * Used for managing IP binding rules based on customer payment status
 */

class MikrotikApi {
    private $socket = false;
    private $debug = false;
    private $connected = false;
    private $host;
    private $port;
    private $timeout;
    private $lastError = '';

    /**
     * Constructor
     * 
     * @param string $host  MikroTik IP address
     * @param int    $port  API-SSL port (default 8729)
     * @param int    $timeout Connection timeout in seconds
     */
    public function __construct($host, $port = 8729, $timeout = 10) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Enable or disable debug mode
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Check if connected
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * Connect to MikroTik API-SSL
     * 
     * @return bool Success/failure
     */
    public function connect() {
        // Create SSL context
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        // Connect via SSL
        $this->socket = @stream_socket_client(
            "ssl://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->lastError = "Connection failed: [$errno] $errstr";
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);
        $this->connected = true;
        return true;
    }

    /**
     * Login to MikroTik
     * 
     * @param string $username API username
     * @param string $password API password
     * @return bool Success/failure
     */
    public function login($username, $password) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        // New auth method (RouterOS 6.43+)
        $this->write('/login', false);
        $this->write('=name=' . $username, false);
        $this->write('=password=' . $password);

        $response = $this->read();

        if (isset($response[0]) && $response[0] === '!done') {
            return true;
        }

        // Extract error message if present
        foreach ($response as $line) {
            if (strpos($line, '=message=') === 0) {
                $this->lastError = "Login failed: " . substr($line, 9);
                return false;
            }
        }

        $this->lastError = "Login failed: Unknown error";
        return false;
    }

    /**
     * Disconnect from MikroTik
     */
    public function disconnect() {
        if ($this->socket) {
            if ($this->debug) echo "<span style='color:gray'>[DEBUG] Disconnecting...</span><br>";
            fclose($this->socket);
            $this->socket = false;
            $this->connected = false;
        }
    }

    /**
     * Write a word to the socket
     * 
     * @param string $word   Word to write
     * @param bool   $isLast Is this the last word in the sentence?
     */
    private function write($word, $isLast = true) {
        if ($this->debug) echo "<span style='color:green'>[DEBUG] >>> Sending: " . htmlspecialchars($word) . "</span><br>";
        
        $len = strlen($word);
        
        // Encode length
        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } elseif ($len < 0x4000) {
            fwrite($this->socket, chr(($len >> 8) | 0x80) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            fwrite($this->socket, chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            fwrite($this->socket, chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }

        // Write word
        fwrite($this->socket, $word);

        // Write empty word to end sentence
        if ($isLast) {
            fwrite($this->socket, chr(0));
            if ($this->debug) echo "<span style='color:green'>[DEBUG] >>> [END OF SENTENCE]</span><br>";
        }
    }

    /**
     * Read a word from the socket
     * 
     * @return string|false Word or false on error
     */
    private function readWord() {
        $byte = fread($this->socket, 1);
        if ($byte === false || strlen($byte) === 0) {
            if ($this->debug) echo "<span style='color:red'>[DEBUG] <<< Read Error or EOF</span><br>";
            return false;
        }

        $len = ord($byte);

        if (($len & 0x80) === 0) {
            // 1 byte length
        } elseif (($len & 0xC0) === 0x80) {
            $len = (($len & 0x3F) << 8) + ord(fread($this->socket, 1));
        } elseif (($len & 0xE0) === 0xC0) {
            $len = (($len & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif (($len & 0xF0) === 0xE0) {
            $len = (($len & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif ($len === 0xF0) {
            $len = (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        if ($len === 0) {
            return '';
        }

        $word = '';
        while (strlen($word) < $len) {
            $chunk = fread($this->socket, $len - strlen($word));
            if ($chunk === false) {
                if ($this->debug) echo "<span style='color:red'>[DEBUG] <<< Read Error (Chunk)</span><br>";
                return false;
            }
            $word .= $chunk;
        }
        
        if ($this->debug) echo "<span style='color:blue'>[DEBUG] <<< Received: " . htmlspecialchars($word) . "</span><br>";

        return $word;
    }

    /**
     * Read response from socket
     * 
     * @return array Response lines
     */
    private function read() {
        $response = [];
        $currentRecord = [];

        while (true) {
            $word = $this->readWord();

            if ($word === false) {
                break;
            }

            if ($word === '') {
                // End of sentence
                if (!empty($currentRecord)) {
                    $response[] = $currentRecord;
                    $currentRecord = [];
                }
                continue;
            }

            // Check for reply type
            if ($word === '!done' || $word === '!trap' || $word === '!fatal') {
                if (!empty($currentRecord)) {
                    $response[] = $currentRecord;
                }
                $response[] = $word;
                
                if ($word === '!done' || $word === '!fatal') {
                    break;
                }
                $currentRecord = [];
                continue;
            }

            if ($word === '!re') {
                if (!empty($currentRecord)) {
                    $response[] = $currentRecord;
                }
                $currentRecord = [];
                continue;
            }

            // Parse attribute
            if (strpos($word, '=') === 0) {
                $parts = explode('=', substr($word, 1), 2);
                if (count($parts) === 2) {
                    $currentRecord[$parts[0]] = $parts[1];
                } else {
                    $currentRecord[$parts[0]] = '';
                }
            } else {
                $response[] = $word;
            }
        }

        if (!empty($currentRecord)) {
            $response[] = $currentRecord;
        }

        return $response;
    }

    /**
     * Execute a command
     * 
     * @param string $command Command to execute
     * @param array  $params  Command parameters
     * @return array Response
     */
    public function command($command, $params = []) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return [];
        }

        // Write command
        $this->write($command, empty($params));

        // Write parameters
        $lastIndex = count($params) - 1;
        $i = 0;
        foreach ($params as $key => $value) {
            if (is_numeric($key)) {
                $this->write($value, $i === $lastIndex);
            } else {
                $this->write("=$key=$value", $i === $lastIndex);
            }
            $i++;
        }

        return $this->read();
    }

    /**
     * Get all IP bindings from hotspot
     * 
     * @return array List of IP bindings
     */
    public function getIpBindings() {
        $response = $this->command('/ip/hotspot/ip-binding/print');
        $bindings = [];

        foreach ($response as $item) {
            if (is_array($item)) {
                $bindings[] = $item;
            }
        }

        return $bindings;
    }

    /**
     * Find IP binding by MAC address
     * 
     * @param string $mac MAC address
     * @return array|null Binding record or null
     */
    public function findBindingByMac($mac) {
        $mac = strtoupper(trim($mac));
        $bindings = $this->getIpBindings();

        foreach ($bindings as $binding) {
            if (isset($binding['mac-address'])) {
                $bindingMac = strtoupper(trim($binding['mac-address']));
                if ($bindingMac === $mac) {
                    return $binding;
                }
            }
        }

        return null;
    }

    /**
     * Set IP binding disabled status
     * 
     * @param string $id       Binding ID (.id from MikroTik)
     * @param bool   $disabled True to disable, false to enable
     * @return bool Success/failure
     */
    public function setBindingDisabled($id, $disabled = true) {
        $response = $this->command('/ip/hotspot/ip-binding/set', [
            '.id' => $id,
            'disabled' => $disabled ? 'yes' : 'no'
        ]);

        foreach ($response as $item) {
            if ($item === '!done') {
                return true;
            }
            if ($item === '!trap' || $item === '!fatal') {
                return false;
            }
        }

        return true;
    }

    /**
     * Enable IP binding by MAC address
     * 
     * @param string $mac MAC address
     * @return bool Success/failure
     */
    public function enableBindingByMac($mac) {
        $binding = $this->findBindingByMac($mac);
        
        if (!$binding) {
            $this->lastError = "Binding not found for MAC: $mac";
            return false;
        }

        if (!isset($binding['.id'])) {
            $this->lastError = "Binding has no ID";
            return false;
        }

        return $this->setBindingDisabled($binding['.id'], false);
    }

    /**
     * Disable IP binding by MAC address
     * 
     * @param string $mac MAC address
     * @return bool Success/failure
     */
    public function disableBindingByMac($mac) {
        $binding = $this->findBindingByMac($mac);
        
        if (!$binding) {
            $this->lastError = "Binding not found for MAC: $mac";
            return false;
        }

        if (!isset($binding['.id'])) {
            $this->lastError = "Binding has no ID";
            return false;
        }

        return $this->setBindingDisabled($binding['.id'], true);
    }

    /**
     * Check if binding is currently disabled
     * 
     * @param string $mac MAC address
     * @return bool|null True if disabled, false if enabled, null if not found
     */
    public function isBindingDisabled($mac) {
        $binding = $this->findBindingByMac($mac);
        
        if (!$binding) {
            return null;
        }

        return isset($binding['disabled']) && $binding['disabled'] === 'true';
    }

    /**
     * Find IP binding by IP address (address or to-address)
     * 
     * @param string $ip IP address
     * @return array|null Binding record or null
     */
    public function findBindingByIp($ip) {
        $ip = trim($ip);
        $bindings = $this->getIpBindings();

        foreach ($bindings as $binding) {
            $address = isset($binding['address']) ? trim($binding['address']) : '';
            $toAddress = isset($binding['to-address']) ? trim($binding['to-address']) : '';
            
            if ($address === $ip || $toAddress === $ip) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * Enable IP binding by IP address
     * 
     * @param string $ip IP address
     * @return bool Success/failure
     */
    public function enableBindingByIp($ip) {
        $binding = $this->findBindingByIp($ip);
        
        if (!$binding) {
            $this->lastError = "Binding not found for IP: $ip";
            return false;
        }

        if (!isset($binding['.id'])) {
            $this->lastError = "Binding has no ID";
            return false;
        }

        return $this->setBindingDisabled($binding['.id'], false);
    }

    /**
     * Disable IP binding by IP address
     * 
     * @param string $ip IP address
     * @return bool Success/failure
     */
    public function disableBindingByIp($ip) {
        $binding = $this->findBindingByIp($ip);
        
        if (!$binding) {
            $this->lastError = "Binding not found for IP: $ip";
            return false;
        }

        if (!isset($binding['.id'])) {
            $this->lastError = "Binding has no ID";
            return false;
        }

        return $this->setBindingDisabled($binding['.id'], true);
    }

    /**
     * Find binding by MAC or IP address (tries MAC first, then IP)
     * 
     * @param string $mac MAC address (can be empty)
     * @param string $ip  IP address (can be empty)
     * @return array|null Binding record or null
     */
    public function findBindingByMacOrIp($mac, $ip) {
        // Try MAC first if valid
        if (!empty($mac) && strlen($mac) >= 12) {
            $binding = $this->findBindingByMac($mac);
            if ($binding) {
                return $binding;
            }
        }

        // Fallback to IP
        if (!empty($ip)) {
            return $this->findBindingByIp($ip);
        }

        return null;
    }
}

/**
 * Helper function to get MikroTik API instance from settings
 * 
 * @param PDO $pdo Database connection
 * @return MikrotikApi|null API instance or null on failure
 */
function getMikrotikApiFromSettings($pdo) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        return null;
    }

    if (empty($settings['mikrotik_enabled']) || $settings['mikrotik_enabled'] !== '1') {
        return null;
    }

    $host = isset($settings['mikrotik_host']) ? trim($settings['mikrotik_host']) : '';
    $port = isset($settings['mikrotik_port']) && $settings['mikrotik_port'] !== '' ? (int)$settings['mikrotik_port'] : null;
    $user = isset($settings['mikrotik_user']) ? trim($settings['mikrotik_user']) : '';
    // Decrypt password from settings
    $encryptedPass = $settings['mikrotik_password'] ?? '';
    $pass = decrypt_data($encryptedPass);

    if (empty($host) || $port === null || empty($user)) {
        error_log("MikroTik API: Configuration in database is incomplete (host, port, or user is empty)");
        return null;
    }

    $api = new MikrotikApi($host, $port);
    
    if (!$api->connect()) {
        error_log("MikroTik API: Failed to connect - " . $api->getLastError());
        return null;
    }

    if (!$api->login($user, $pass)) {
        error_log("MikroTik API: Failed to login - " . $api->getLastError());
        $api->disconnect();
        return null;
    }

    return $api;
}

/**
 * Sync a single customer's IP binding based on their invoice status
 * Uses MAC address or IP address to find the binding
 * 
 * @param PDO         $pdo        Database connection
 * @param MikrotikApi $api        MikroTik API instance
 * @param int         $customerId Customer ID
 * @return array      Result with 'success' and 'message' keys
 */
function syncCustomerBinding($pdo, $api, $customerId) {
    // Get customer with IP address
    $stmt = $pdo->prepare("SELECT id, name, mac_address, ip_address, status FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        return ['success' => false, 'message' => 'Customer not found'];
    }

    $mac = strtoupper(trim($customer['mac_address'] ?? ''));
    $ip = trim($customer['ip_address'] ?? '');
    
    // Check if we have valid MAC or IP
    $hasValidMac = !empty($mac) && strlen($mac) >= 12 && $mac !== '\\\\';
    $hasValidIp = !empty($ip);
    
    if (!$hasValidMac && !$hasValidIp) {
        return ['success' => false, 'message' => 'No valid MAC or IP address', 'skipped' => true];
    }

    // Find binding by MAC or IP
    $binding = $api->findBindingByMacOrIp($mac, $ip);
    
    if (!$binding) {
        return ['success' => false, 'message' => "Binding not found for MAC: $mac or IP: $ip", 'skipped' => true];
    }

    if (!isset($binding['.id'])) {
        return ['success' => false, 'message' => 'Binding has no ID'];
    }

    // Check for overdue invoices (status = 'overdue' or unpaid past due_date)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM invoices 
        WHERE customer_id = ? 
        AND (status = 'overdue' OR (status = 'unpaid' AND due_date < CURDATE()))
    ");
    $stmt->execute([$customerId]);
    $overdueCount = (int)$stmt->fetchColumn();

    // Determine if should disable: overdue invoices OR customer not active
    $shouldDisable = ($overdueCount > 0) || ($customer['status'] !== 'active');

    // Apply action
    $result = $api->setBindingDisabled($binding['.id'], $shouldDisable);
    $action = $shouldDisable ? 'disabled' : 'enabled';

    if ($result) {
        return ['success' => true, 'message' => "Binding $action for {$customer['name']}", 'action' => $action];
    } else {
        return ['success' => false, 'message' => $api->getLastError()];
    }
}
?>
