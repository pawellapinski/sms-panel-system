<!DOCTYPE html>
<html>
<head>
    <title>Monitor SMS</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #sms-list { margin-top: 20px; }
        .sms-item {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .status { margin-bottom: 10px; }
        .error { color: red; }
        .success { color: green; }
        .refresh-btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .logs-btn {
            padding: 5px 10px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .last-sms {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h2>Monitor SMS</h2>

    <div class="status">
        Status serwera: <span id="server-status">Sprawdzanie...</span>
    </div>

    <div class="status">
        IP serwera: <span id="server-ip">Sprawdzanie...</span>
    </div>

    <button class="refresh-btn" onclick="refreshSMSList()">Odśwież</button>
    <button class="logs-btn" onclick="openLogs()">Zobacz logi</button>

    <div id="last-sms" class="last-sms">
        <h3>Ostatni odebrany SMS:</h3>
        <div id="last-sms-content">Ładowanie...</div>
    </div>

    <div id="sms-list"></div>

    <script>
        // Base URL
        const baseUrl = window.location.protocol + '//' + window.location.host;

        // Sprawdź status serwera
        async function checkServerStatus() {
            try {
                const response = await fetch(baseUrl + '/status');
                const data = await response.json();

                document.getElementById('server-status').textContent = data.status || 'Nieznany';
                document.getElementById('server-status').className = 'success';

                const serverIp = data.server_ip || data.client_ip || 'Nieznany';
                document.getElementById('server-ip').textContent = serverIp + ':' + window.location.port;

            } catch (error) {
                document.getElementById('server-status').textContent = 'Błąd połączenia';
                document.getElementById('server-status').className = 'error';
                console.error('Błąd sprawdzania statusu:', error);
            }
        }

        // Pobierz ostatniego SMS-a
        async function getLastSMS() {
            try {
                const response = await fetch(baseUrl + '/api/last-sms');
                const data = await response.json();

                const lastSmsContent = document.getElementById('last-sms-content');

                if (data.status === 'success' && data.sms) {
                    const sms = data.sms;
                    lastSmsContent.innerHTML = `
                        <p><strong>Od:</strong> ${sms.phone_number}</p>
                        <p><strong>Treść:</strong> ${sms.message}</p>
                        <p><strong>Czas:</strong> ${sms.received_at}</p>
                        <p><strong>ID:</strong> ${sms.id}</p>
                        <p><strong>SIM:</strong> ${sms.sim_number || 'Nieznany'}</p>
                    `;
                } else {
                    lastSmsContent.innerHTML = 'Brak SMS-ów w bazie danych.';
                }

            } catch (error) {
                document.getElementById('last-sms-content').innerHTML = 'Błąd: Nie udało się pobrać ostatniego SMS-a.';
                console.error('Błąd pobierania ostatniego SMS:', error);
            }
        }

        // Pobierz listę SMS-ów
        async function refreshSMSList() {
            try {
                const response = await fetch(baseUrl + '/api/sms-list');
                const data = await response.json();

                const smsList = document.getElementById('sms-list');
                smsList.innerHTML = '<h3>Lista SMS-ów:</h3>';

                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(sms => {
                        const smsItem = document.createElement('div');
                        smsItem.className = 'sms-item';

                        const phoneNumber = document.createElement('p');
                        phoneNumber.innerHTML = '<strong>Od:</strong> ' + sms.phone_number;

                        const message = document.createElement('p');
                        message.innerHTML = '<strong>Treść:</strong> ' + sms.message;

                        const time = document.createElement('p');
                        time.innerHTML = '<strong>Czas:</strong> ' + (sms.received_at || 'Nieznany');

                        const id = document.createElement('p');
                        id.innerHTML = '<strong>ID:</strong> ' + sms.id;

                        smsItem.appendChild(phoneNumber);
                        smsItem.appendChild(message);
                        smsItem.appendChild(time);
                        smsItem.appendChild(id);

                        smsList.appendChild(smsItem);
                    });
                } else {
                    smsList.innerHTML += '<p>Brak SMS-ów.</p>';
                }

                // Zaktualizuj też ostatniego SMS-a
                getLastSMS();

            } catch (error) {
                document.getElementById('sms-list').innerHTML = '<p class="error">Błąd: Nie udało się pobrać listy SMS-ów. ' + error.message + '</p>';
                console.error('Błąd pobierania listy SMS:', error);
            }
        }

        function openLogs() {
            window.open(baseUrl + '/laravel-logs', '_blank');
        }

        // Uruchom funkcje po załadowaniu strony
        window.onload = function() {
            checkServerStatus();
            getLastSMS();
            refreshSMSList();

            // Automatyczne odświeżanie co 10 sekund
            setInterval(() => {
                getLastSMS();
                refreshSMSList();
            }, 10000);
        };
    </script>
</body>
</html>
