<!DOCTYPE html>
<html>
<head>
    <title>Test Webhook</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { padding: 20px; font-family: Arial; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h2>Test Webhook</h2>
    
    <h3>Przykładowy payload (z webhook.site):</h3>
    <pre id="samplePayload">{
  "deviceId": "ffffffff9496f2ca00000195ed2bb800",
  "event": "sms:received",
  "id": "RNZqtZddgi7FWL6Cl43wn",
  "payload": {
    "message": "Test7",
    "receivedAt": "2025-04-01T13:17:13.000+02:00",
    "messageId": "5d266bc0",
    "phoneNumber": "+48504399970",
    "simNumber": 2
  },
  "webhookId": "infoSMS"
}</pre>

    <button onclick="testWebhook('/webhook')">Test głównego endpointu</button>
    <button onclick="testWebhook('/webhook-debug')">Test debug endpointu</button>

    <div id="result" style="margin-top: 20px;"></div>

    <script>
    function testWebhook(endpoint) {
        const payload = JSON.parse(document.getElementById('samplePayload').textContent);
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`http://192.168.1.115:8000${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').innerHTML = 
                `<h4>Odpowiedź:</h4><pre>${JSON.stringify(data, null, 2)}</pre>`;
        })
        .catch(error => {
            document.getElementById('result').innerHTML = 
                `<div style="color: red;">Błąd: ${error.message}</div>`;
        });
    }
    </script>
</body>
</html> 