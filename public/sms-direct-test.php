<?php
// Ten plik pozwala bezpośrednio przetestować zapisywanie SMS-ów bez pośrednictwa ngrok

// Pobierz dane przychodzące (można je testować także przez przeglądarkę)
$content = file_get_contents('php://input');
if (empty($content)) {
    // Przykładowe dane do testu
    $content = '{
        "deviceId": "test-device",
        "event": "sms:received",
        "id": "test-id",
        "payload": {
            "message": "To jest testowy SMS bez ngrok",
            "receivedAt": "2025-04-03T15:00:00.000+02:00",
            "messageId": "test-direct",
            "phoneNumber": "+48123456789",
            "simNumber": 1
        },
        "webhookId": "test-webhook"
    }';
}

// Zapisz dane do pliku dla analizy
$logFile = __DIR__ . '/../storage/logs/sms-direct-test.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Otrzymano dane:\n" . $content . "\n\n", FILE_APPEND);

// Parsuj JSON
$data = json_decode($content, true);

try {
    // Sprawdź czy dane mają poprawny format
    if ($data && isset($data['event']) && $data['event'] === 'sms:received' && isset($data['payload'])) {
        $payload = $data['payload'];
        
        // Połącz z bazą danych
        $pdo = new PDO('mysql:host=localhost;dbname=smssystem', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Sprawdź czy tabela istnieje
        try {
            $checkTable = $pdo->query("SELECT 1 FROM sms_messages LIMIT 1");
        } catch (PDOException $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Błąd: Tabela sms_messages nie istnieje\n\n", FILE_APPEND);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Tabela sms_messages nie istnieje w bazie danych'
            ]);
            exit;
        }
        
        // Przygotuj dane
        $messageId = $payload['messageId'] ?? null;
        $phoneNumber = $payload['phoneNumber'] ?? null;
        $message = $payload['message'] ?? null;
        $simNumber = $payload['simNumber'] ?? null;
        $receivedAt = $payload['receivedAt'] ?? date('Y-m-d H:i:s');
        $deviceId = $data['deviceId'] ?? null;
        $webhookId = $data['webhookId'] ?? null;
        $rawPayload = $content;
        
        // Zapisz SMS do bazy danych
        $stmt = $pdo->prepare("INSERT INTO sms_messages 
            (message_id, phone_number, message, sim_number, received_at, device_id, webhook_id, raw_payload, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->execute([
            $messageId, 
            $phoneNumber, 
            $message, 
            $simNumber, 
            $receivedAt, 
            $deviceId, 
            $webhookId, 
            $rawPayload
        ]);
        
        $id = $pdo->lastInsertId();
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - SMS zapisany z ID: $id\n\n", FILE_APPEND);
        
        // Zwróć sukces
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'SMS został zapisany bezpośrednio',
            'id' => $id
        ]);
    } else {
        // Zwróć błąd formatu danych
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Niepoprawny format danych\n\n", FILE_APPEND);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Niepoprawny format danych',
            'received' => $data
        ]);
    }
} catch (Exception $e) {
    // Zwróć błąd
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Błąd: " . $e->getMessage() . "\n\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Wystąpił błąd: ' . $e->getMessage()
    ]);
} 