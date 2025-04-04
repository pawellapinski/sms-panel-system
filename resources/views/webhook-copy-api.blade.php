<!DOCTYPE html>
<html>
<head>
    <title>Kopiowanie SMS z webhook.site (API)</title>
    <meta charset="utf-8">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        textarea { width: 100%; height: 200px; margin: 10px 0; }
        button { padding: 10px 20px; }
        #result { margin-top: 20px; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Kopiowanie SMS z webhook.site (API)</h1>
    <p>Wklej surowe dane JSON z webhook.site:</p>
    <textarea id="jsonData" placeholder="Wklej tutaj dane JSON..."></textarea>
    <button onclick="sendData()">Wyślij do systemu</button>
    <div id="result"></div>

    <script>
        async function sendData() {
            const jsonData = document.getElementById('jsonData').value;
            const result = document.getElementById('result');
            
            try {
                const response = await fetch('/api/webhook-copy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: jsonData
                });
                
                const data = await response.json();
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                
                if (data.status === 'success') {
                    result.style.backgroundColor = '#e6ffe6';
                } else {
                    result.style.backgroundColor = '#ffe6e6';
                }
            } catch (error) {
                result.innerHTML = `Błąd: ${error.message}`;
                result.style.backgroundColor = '#ffe6e6';
            }
        }
    </script>
</body>
</html> 