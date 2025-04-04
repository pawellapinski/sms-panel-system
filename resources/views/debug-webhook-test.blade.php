<!DOCTYPE html>
<html>
<head>
    <title>Test Debug Webhook</title>
    <style>
        body { padding: 20px; font-family: Arial; }
        .form-group { margin-bottom: 15px; }
        textarea { width: 100%; height: 200px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; }
        #result { margin-top: 20px; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h2>Test Debug Webhook</h2>
    
    <div class="form-group">
        <label>Przykładowy payload SMS:</label>
        <textarea id="payload">{
    "deviceId": "ffffffff9496f2ca00000195ed2bb800",
    "event": "sms:received",
    "id": "RNZqtZddgi7FWL6Cl43wn",
    "payload": {
        "message": "Test SMS",
        "receivedAt": "2025-04-01T13:17:13.000+02:00",
        "messageId": "5d266bc0",
        "phoneNumber": "+48504399970",
        "simNumber": 2
    },
    "webhookId": "infoSMS"
}</textarea>
    </div>

    <button onclick="testWebhook()">Wyślij testowy SMS</button>
    <button onclick="testRealEndpoint()">Wyślij na prawdziwy endpoint</button>

    <div id="result"></div>

    <script>
        function testWebhook() {
            sendRequest('/debug-webhook');
        }

        function testRealEndpoint() {
            sendRequest('/webhook');
        }

        function sendRequest(url) {
            fetch('http://192.168.1.115:8000' + url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: document.getElementById('payload').value
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerHTML = 
                    '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                document.getElementById('result').innerHTML = 
                    '<div style="color: red;">Błąd: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html> 