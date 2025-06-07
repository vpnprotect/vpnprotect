<?php
// Include VPN protection at the top of your protected page
include_once 'vpn_protection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protected Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            border: 2px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            background-color: #d4edda;
            margin-bottom: 20px;
        }
        .info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">
            <h2>✅ Access Granted!</h2>
            <p>Your IP address has been verified and you have access to this protected content.</p>
        </div>
        
        <h1>Protected Page Demo</h1>
        <p>This page is protected by VPN detection. Only visitors with legitimate IP addresses can access this content.</p>
        
        <h2>How it works:</h2>
        <ol>
            <li>Your IP address is detected: <strong><?php echo $_SERVER['REMOTE_ADDR']; ?></strong></li>
            <li>The system checks if your IP is whitelisted</li>
            <li>If not whitelisted, it queries the getipintel.net API</li>
            <li>If the VPN probability is above the configured threshold, access is denied</li>
            <li>Otherwise, you see this page!</li>
        </ol>
        
        <div class="info">
            <h3>Implementation</h3>
            <p>To protect any PHP page, simply add this line at the very top:</p>
            <code>&lt;?php include_once 'vpn_protection.php'; ?&gt;</code>
            
            <h3>Configuration</h3>
            <p>Edit <code>config.php</code> to customize:</p>
            <ul>
                <li>Your email address for the API</li>
                <li>P-value threshold (VPN probability)</li>
                <li>Failure page URL</li>
                <li>IP whitelist</li>
                <li>Caching and logging settings</li>
            </ul>
        </div>
    </div>
</body>
</html>