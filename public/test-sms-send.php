<?php
$url = 'https://da8d-193-0-208-37.ngrok-free.app/sms-direct';

$data = [
    'deviceId' => 'test-device-id',
    'event' => 'sms:received',
    'id' => 'test-id',
    'payload' => [
        'messageId' => 'test-message-id',
        'message' => 'To jest testowy SMS z test-sms-send.php',
        'phoneNumber' => '123456789',
        'simNumber' => 1,
        'receivedAt' => date('Y-m-d\TH:i:s.000+00:00')
    ],
    'webhookId' => 'test-webhook-id'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n"; 