<?php
/**
 * VPN Protection Configuration
 * Configure these settings according to your needs
 */

// Your email address for getipintel.net API (required)
$config['email'] = 'your-email@example.com';

// P-value threshold (0.0 to 1.0) - IPs with VPN probability above this will be blocked
// 0.95 means 95% probability threshold
$config['p_value'] = 0.95;

// Page to redirect blocked users to
$config['failure_page'] = 'blocked.html';

// IP whitelist - these IPs will always be allowed (optional)
// Add your own IP addresses here to avoid blocking yourself
$config['ip_whitelist'] = [
    '127.0.0.1',        // localhost
    '::1',              // localhost IPv6
    // Add more IPs as needed
    // '192.168.1.100',
    // '203.0.113.1',
];

// Enable/disable logging (optional)
$config['enable_logging'] = true;
$config['log_file'] = 'vpn_protection.log';

// Cache settings to avoid repeated API calls for same IP
$config['enable_cache'] = true;
$config['cache_duration'] = 3600; // 1 hour in seconds
$config['cache_file'] = 'ip_cache.json';

?>