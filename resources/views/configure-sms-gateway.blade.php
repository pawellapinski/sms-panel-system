<!DOCTYPE html>
<html>
<head>
    <title>Konfiguracja SMS Gateway</title>
    <style>
        body { padding: 20px; font-family: Arial; }
        .form-group { margin-bottom: 15px; }
        input[type="text"] { width: 100%; padding: 8px; margin: 5px 0; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; }
    </style>
</head>
<body>
    <h2>Konfiguracja SMS Gateway</h2>
    
    <form method="POST" action="/send-sms-config" id="configForm">
        @csrf
        <div class="form-group">
            <label>URL dla SMS Gateway (gdzie ma wysyłać SMS-y):</label>
            <input type="text" name="url" value="http://192.168.1.115:8000/webhook" required>
            <small>To jest adres Twojej aplikacji Laravel</small>
        </div>

        <div class="form-group">
            <label>ID:</label>
            <input type="text" name="id" value="infoSMS" required>
        </div>

        <div class="form-group">
            <label>Event:</label>
            <input type="text" name="event" value="sms:received" required>
        </div>

        <button type="submit">Zapisz konfigurację</button>
    </form>

    <div id="result" style="margin-top: 20px;"></div>

    <script>
        document.getElementById('configForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('/send-sms-config', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    url: document.querySelector('input[name="url"]').value,
                    id: document.querySelector('input[name="id"]').value,
                    event: document.querySelector('input[name="event"]').value
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerHTML = 
                    `<div style="padding: 10px; background: ${data.status === 'success' ? '#dff0d8' : '#f2dede'};">
                        ${data.message}
                    </div>`;
            })
            .catch(error => {
                document.getElementById('result').innerHTML = 
                    `<div style="padding: 10px; background: #f2dede;">
                        Błąd: ${error.message}
                    </div>`;
            });
        });
    </script>
</body>
</html> 