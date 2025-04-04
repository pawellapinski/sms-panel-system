<!DOCTYPE html>
<html>
<head>
    <title>Test SMS Gateway</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { padding: 20px; font-family: Arial; }
        pre { background: #f5f5f5; padding: 10px; }
        button { margin: 10px 0; padding: 5px 10px; }
        #result { margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Test SMS Gateway Debug</h2>
    
    <div>
        <p>Local Address SMS Gateway: 192.168.1.86:8089</p>
        <p>Laravel Address: 192.168.1.115:8000</p>
    </div>

    <button onclick="testEndpoint()">Wyślij testowe dane</button>
    <button onclick="checkConnection()">Sprawdź połączenie</button>
    
    <div id="result"></div>

    <script>
    function testEndpoint() {
        const testData = {
            deviceId: "test-device",
            event: "sms:received",
            payload: {
                message: "Test message",
                phoneNumber: "+48123456789",
                receivedAt: new Date().toISOString(),
                messageId: "test-" + Date.now(),
                simNumber: 1
            },
            webhookId: "infoSMS"
        };

        fetch('/sms-gateway-debug', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(testData)
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

    function checkConnection() {
        fetch('/test-phone-connection')
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').innerHTML = 
                '<h4>Status połączenia:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('result').innerHTML = 
                '<div style="color: red;">Błąd połączenia: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html> 