<!DOCTYPE html>
<html>
<head>
    <title>Test SMS Endpoint</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        textarea { width: 100%; height: 200px; margin-bottom: 10px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test endpointu SMS</h1>
        
        <form id="smsForm">
            <h3>Testowe dane SMS:</h3>
            <textarea id="smsPayload">{
  "deviceId": "ffffffffceb0b1db0000018e937c815b",
  "event": "sms:received",
  "id": "Ey6ECgOkVVFjz3CL48B8C",
  "payload": {
    "messageId": "test123",
    "message": "To jest testowy SMS",
    "phoneNumber": "123456789",
    "simNumber": 1,
    "receivedAt": "2024-04-03T15:46:11.000+07:00"
  },
  "webhookId": "laravel-webhook"
}</textarea>
            <br>
            <button type="button" onclick="sendTestSms()">Wyślij testowy SMS</button>
        </form>
        
        <div id="result" style="margin-top: 20px;"></div>
        
        <script>
            function sendTestSms() {
                const payload = document.getElementById('smsPayload').value;
                
                fetch('/api/sms-direct-test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: payload
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('result').innerHTML = 
                        `<h3>Odpowiedź:</h3>
                         <pre>${JSON.stringify(data, null, 2)}</pre>`;
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = 
                        `<h3>Błąd:</h3>
                         <pre>${error}</pre>`;
                });
            }
        </script>
    </div>
</body>
</html> 