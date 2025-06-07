<?php
/**
 * VPN Protection Include File
 * Include this file at the top of any page you want to protect
 * Example: include_once 'vpn_protection.php';
 */

// Include configuration
require_once 'config.php';

class VPNProtection {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Get the real IP address of the visitor
     */
    private function getRealIP() {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancers/proxies
            'HTTP_X_FORWARDED',          // Proxies
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster environments
            'HTTP_FORWARDED_FOR',        // Standard forwarded header
            'HTTP_FORWARDED',            // Standard forwarded header
            'REMOTE_ADDR'                // Standard remote address
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated list (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR even if it's private/reserved
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Check if IP is in whitelist
     */
    private function isWhitelisted($ip) {
        return in_array($ip, $this->config['ip_whitelist']);
    }
    
    /**
     * Load cached IP results
     */
    private function loadCache() {
        if (!$this->config['enable_cache'] || !file_exists($this->config['cache_file'])) {
            return [];
        }
        
        $cache = json_decode(file_get_contents($this->config['cache_file']), true);
        if (!$cache) {
            return [];
        }
        
        // Clean expired entries
        $now = time();
        foreach ($cache as $ip => $data) {
            if ($now - $data['timestamp'] > $this->config['cache_duration']) {
                unset($cache[$ip]);
            }
        }
        
        return $cache;
    }
    
    /**
     * Save cache
     */
    private function saveCache($cache) {
        if (!$this->config['enable_cache']) {
            return;
        }
        
        file_put_contents($this->config['cache_file'], json_encode($cache, JSON_PRETTY_PRINT));
    }
    
    /**
     * Query getipintel.net API
     */
    private function queryAPI($ip) {
        $url = "http://check.getipintel.net/check.php?" . http_build_query([
            'ip' => $ip,
            'contact' => $this->config['email'],
            'format' => 'json'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'VPN Protection Script'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $this->log("API Error: Failed to connect to getipintel.net for IP: $ip");
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("API Error: Invalid JSON response for IP: $ip");
            return false;
        }
        
        // Check for API errors
        if (isset($data['status']) && $data['status'] === 'error') {
            $this->log("API Error: " . ($data['message'] ?? 'Unknown error') . " for IP: $ip");
            return false;
        }
        
        return $data;
    }
    
    /**
     * Log events
     */
    private function log($message) {
        if (!$this->config['enable_logging']) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->config['log_file'], $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Main protection method
     */
    public function protect() {
        $ip = $this->getRealIP();
        
        // Check whitelist first
        if ($this->isWhitelisted($ip)) {
            $this->log("Access granted: IP $ip is whitelisted");
            return true;
        }
        
        // Load cache
        $cache = $this->loadCache();
        
        // Check if IP is in cache
        if (isset($cache[$ip])) {
            $probability = $cache[$ip]['probability'];
            $this->log("Cache hit: IP $ip has probability $probability");
        } else {
            // Query API
            $result = $this->queryAPI($ip);
            
            if ($result === false) {
                // If API fails, allow access but log the issue
                $this->log("API failure: Allowing access for IP $ip due to API error");
                return true;
            }
            
            $probability = floatval($result['result'] ?? 0);
            
            // Cache the result
            $cache[$ip] = [
                'probability' => $probability,
                'timestamp' => time()
            ];
            $this->saveCache($cache);
            
            $this->log("API query: IP $ip has probability $probability");
        }
        
        // Check if probability exceeds threshold
        if ($probability > $this->config['p_value']) {
            $this->log("Access denied: IP $ip probability ($probability) exceeds threshold (" . $this->config['p_value'] . ")");
            $this->redirect();
            return false;
        }
        
        $this->log("Access granted: IP $ip probability ($probability) is below threshold (" . $this->config['p_value'] . ")");
        return true;
    }
    
    /**
     * Redirect to failure page
     */
    private function redirect() {
        header("Location: " . $this->config['failure_page']);
        exit;
    }
}

// Initialize and run protection
$protection = new VPNProtection($config);
$protection->protect();

?>