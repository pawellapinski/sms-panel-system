<!DOCTYPE html>
<html>
<head>
    <title>Test SMS Gateway</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { padding: 20px; font-family: Arial; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 8px; }
        button { padding: 10px 20px; }
        #result { margin-top: 20px; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h2>Test SMS Gateway</h2>
    
    <div class="form-group">
        <label>URL:</label>
        <input type="text" id="url" value="http://192.168.1.115:8000/sms-debug">
    </div>

    <div class="form-group">
        <label>Payload:</label>
        <textarea id="payload" rows="10">{
    "deviceId": "test-device-id",
    "event": "sms:received",
    "id": "test-id",
    "payload": {
        "message": "Test SMS message",
        "receivedAt": "2025-04-01T13:17:13.000+02:00",
        "messageId": "test-msg-id",
        "phoneNumber": "+48123456789",
        "simNumber": 1
    },
    "webhookId": "infoSMS"
}</textarea>
    </div>

    <button onclick="sendTest()">Wyślij testowy SMS</button>
    <div id="result"></div>

    <script>
    function sendTest() {
        const url = document.getElementById('url').value;
        const payload = JSON.parse(document.getElementById('payload').value);
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(url, {
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
                '<h4>Odpowiedź:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('result').innerHTML = 
                '<div style="color: red;">Błąd: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html> 