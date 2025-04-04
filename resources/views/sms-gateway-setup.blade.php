<!DOCTYPE html>
<html>
<head>
    <title>Konfiguracja SMS Gateway</title>
    <style>
        body { padding: 20px; font-family: Arial; }
        .config-box {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .config-item {
            margin: 10px 0;
        }
        .label { font-weight: bold; }
        .value { 
            background: #f5f5f5;
            padding: 5px 10px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h2>Konfiguracja SMS Gateway</h2>
    
    <div class="config-box">
        <h3>Ustawienia do skonfigurowania w aplikacji SMS Gateway:</h3>
        
        <div class="config-item">
            <div class="label">URL:</div>
            <div class="value">http://192.168.1.115:8000/sms-receive</div>
        </div>
        
        <div class="config-item">
            <div class="label">ID:</div>
            <div class="value">infoSMS</div>
        </div>
        
        <div class="config-item">
            <div class="label">Event:</div>
            <div class="value">sms:received</div>
        </div>
    </div>

    <div class="config-box">
        <h3>Kroki do wykonania:</h3>
        <ol>
            <li>Otwórz aplikację SMS Gateway na telefonie</li>
            <li>Przejdź do ustawień webhook</li>
            <li>Wprowadź powyższy URL</li>
            <li>Ustaw ID na "infoSMS"</li>
            <li>Ustaw Event na "sms:received"</li>
            <li>Zapisz ustawienia</li>
            <li>Wyślij testowy SMS na numer telefonu</li>
        </ol>
    </div>

    <div class="config-box">
        <h3>Sprawdzanie działania:</h3>
        <ul>
            <li><a href="/sms-monitor" target="_blank">Monitor SMS</a> - zobacz przychodzące SMS-y w czasie rzeczywistym</li>
            <li><a href="/sms-list" target="_blank">Lista SMS</a> - przeglądaj zapisane SMS-y</li>
            <li><a href="/sms-logs" target="_blank">Logi</a> - sprawdź logi systemu</li>
        </ul>
    </div>
</body>
</html> 