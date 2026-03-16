<?php
// test_whatsapp.php - Test UltraMsg Connection
echo "<h2>🔧 UltraMsg Connection Test</h2>";

// YOUR CREDENTIALS
$token = 'u1r28xma3d0ir71a';
$instance_id = '165224';
$test_phone = '256XXXXXXXXX'; // Replace with YOUR test phone number (include country code)

echo "<h3>Testing with:</h3>";
echo "Token: " . substr($token, 0, 5) . "..." . substr($token, -5) . "<br>";
echo "Instance ID: $instance_id<br>";
echo "Test Phone: $test_phone<br><br>";

// Test 1: Check if instance exists
echo "<h4>Test 1: Checking Instance Status...</h4>";
$status_url = "https://api.ultramsg.com/$instance_id/instance/status?token=$token";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $status_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Response: " . htmlspecialchars($response) . "<br>";

if ($http_code == 200) {
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] == 'online') {
        echo "✅ Instance is ONLINE!<br>";
    } else {
        echo "❌ Instance is not online. Status: " . ($data['status'] ?? 'unknown') . "<br>";
        echo "You need to scan QR code in UltraMsg dashboard to connect WhatsApp!<br>";
    }
} else {
    echo "❌ Cannot connect to UltraMsg API. Check token and instance ID.<br>";
}

echo "<hr>";

// Test 2: Try sending a message (if instance is online)
if ($http_code == 200) {
    echo "<h4>Test 2: Sending Test Message...</h4>";
    
    $send_url = "https://api.ultramsg.com/$instance_id/messages/chat";
    $data = [
        'token' => $token,
        'to' => $test_phone,
        'body' => "Test message from Rays of Grace School - " . date('Y-m-d H:i:s'),
        'priority' => 10
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $send_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $http_code<br>";
    echo "Response: " . htmlspecialchars($response) . "<br>";
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        if (isset($result['sent']) && $result['sent'] == 'true') {
            echo "✅✅✅ MESSAGE SENT SUCCESSFULLY! ✅✅✅<br>";
        } else {
            echo "❌ Message failed to send. Response: " . print_r($result, true) . "<br>";
        }
    } else {
        echo "❌ Failed to send. Check your phone number format.<br>";
    }
}

echo "<hr>";
echo "<h3>Troubleshooting Tips:</h3>";
echo "1. Go to https://ultramsg.com/ and login<br>";
echo "2. Check if your instance (165224) is active<br>";
echo "3. Make sure you've scanned the QR code with your phone<br>";
echo "4. Verify your WhatsApp is connected in the dashboard<br>";
echo "5. Check that your phone number format includes country code (256 for Uganda)<br>";
?>