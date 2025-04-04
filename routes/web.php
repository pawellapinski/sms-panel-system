<?php
ini_set('max_execution_time', 30); // standardowe 30 sekund

// Wszystkie deklaracje use na początku pliku
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\SmsWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\SmsMessage;

// Dodaj tę trasę na samym początku pliku, przed innymi trasami
Route::any('/sms-direct', function (Request $request) {
    // Logowanie wszystkich danych wejściowych
    Log::info('SMS-direct - otrzymano dane', [
        'method' => $request->method(),
        'ip' => $request->ip(),
        'content' => $request->getContent(),
        'headers' => $request->headers->all()
    ]);
    
    try {
        // Parsowanie JSON
        $content = $request->getContent();
        $data = json_decode($content, true);
        
        // Zapisz surowe dane do pliku dla analizy
        $logFile = storage_path('logs/sms-raw-' . date('Y-m-d') . '.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $content . "\n\n", FILE_APPEND);
        
        // Sprawdź czy dane mają poprawny format
        if ($data && isset($data['event']) && $data['event'] === 'sms:received' && isset($data['payload'])) {
            $payload = $data['payload'];
            
            // Zapewnienie poprawnego formatu danych
            $messageId = $payload['messageId'] ?? null;
            $phoneNumber = $payload['phoneNumber'] ?? null;
            $message = $payload['message'] ?? null;
            $simNumber = $payload['simNumber'] ?? null;
            $receivedAt = $payload['receivedAt'] ?? now()->toDateTimeString();
            
            // Tworzenie nowego SMS-a
            $sms = new SmsMessage();
            $sms->message_id = $messageId;
            $sms->phone_number = $phoneNumber;
            $sms->message = $message;
            $sms->sim_number = $simNumber;
            $sms->received_at = $receivedAt;
            $sms->device_id = $data['deviceId'] ?? null;
            $sms->webhook_id = $data['webhookId'] ?? null;
            $sms->raw_payload = $content;
            $sms->save();
            
            Log::info('SMS zapisany pomyślnie', [
                'id' => $sms->id,
                'message' => $message
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'SMS został odebrany i zapisany',
                'id' => $sms->id
            ]);
        } else {
            Log::warning('Niepoprawny format danych SMS', [
                'data' => $data
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Niepoprawny format danych'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Błąd podczas przetwarzania SMS', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Wystąpił błąd: ' . $e->getMessage()
        ]);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Upewnij się, że ta trasa jest na początku pliku, zaraz po deklaracjach use
Route::any('/', function (Request $request) {
    // Używamy standardowego systemu logowania Laravel
    Log::info('Webhook received', [
        'ip' => $request->ip(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent()
    ]);
    
    try {
        $content = $request->getContent();
        $data = json_decode($content, true);
        
        if ($data) {
            Log::info('Parsed webhook data', ['data' => $data]);
            
            if (isset($data['payload'])) {
                $smsData = $data['payload'];
                
                $smsMessage = \App\Models\SmsMessage::create([
                    'device_id' => $data['deviceId'] ?? null,
                    'message_id' => $smsData['messageId'] ?? null,
                    'phone_number' => $smsData['phoneNumber'] ?? '',
                    'message' => $smsData['message'] ?? '',
                    'received_at' => now(),
                    'sim_number' => $smsData['simNumber'] ?? null,
                    'webhook_id' => $data['webhookId'] ?? null,
                    'raw_payload' => json_encode($data)
                ]);
                
                Log::info('SMS saved successfully', [
                    'id' => $smsMessage->id,
                    'message' => $smsData['message'] ?? ''
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS zapisany',
                    'id' => $smsMessage->id
                ]);
            }
        }
        
        Log::warning('Invalid webhook data format', [
            'content' => $content
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Nieprawidłowy format danych'
        ], 400);
        
    } catch (\Exception $e) {
        Log::error('Webhook error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->middleware(['api'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Dodaj trasę dla sprawdzenia statusu
Route::get('/status', function () {
    return response()->json([
        'status' => 'running',
        'time' => now()->format('Y-m-d H:i:s'),
        'server_ip' => request()->server('SERVER_ADDR'),
        'client_ip' => request()->ip()
    ]);
});

// Zakomentuj tę trasę, ponieważ koliduje z główną trasą /
// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/generate-token', function () {
    return Str::random(64);
})->middleware('auth');

Route::post('/webhook', function (Request $request) {
    Log::info('Webhook - Nowe żądanie na /webhook', [
        'ip' => $request->ip(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'all' => $request->all()
    ]);
    
    try {
        $controller = new \App\Http\Controllers\SmsWebhookController();
        $result = $controller->handle($request);
        
        Log::info('Webhook - Rezultat:', [
            'response' => $result->getData()
        ]);
        
        return $result;
    } catch (\Exception $e) {
        Log::error('Webhook - Błąd w trasie:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/sms-webhook', [App\Http\Controllers\SmsWebhookController::class, 'handle']);

Route::get('/test', function () {
    return response()->json(['message' => 'Test endpoint works!']);
});

Route::get('/test-webhook', function () {
    return response()->json(['status' => 'GET works']);
});

Route::post('/simple-post-test', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'received' => $request->all()
    ]);
});

Route::post('/test-no-middleware', function (Request $request) {
    return response()->json(['status' => 'ok']);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/test-manual-json', function (Request $request) {
    $content = $request->getContent();
    $data = json_decode($content, true);
    return response()->json([
        'status' => 'ok',
        'parsed' => $data
    ]);
});

Route::get('/test-form', function () {
    return '<form method="POST" action="/test-post-form">
        <input type="hidden" name="_token" value="' . csrf_token() . '">
        <input type="text" name="test" value="message">
        <button type="submit">Send</button>
    </form>';
});

Route::post('/test-post-form', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'received' => $request->all()
    ]);
});

Route::get('/php-info', function () {
    return response()->json([
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'max_input_time' => ini_get('max_input_time'),
        'max_input_vars' => ini_get('max_input_vars'),
        'file_uploads' => ini_get('file_uploads'),
        'display_errors' => ini_get('display_errors'),
        'log_errors' => ini_get('log_errors')
    ]);
});

Route::get('/sms-logs', function () {
    try {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return 'Plik logów nie istnieje.';
        }
        
        $logs = file_get_contents($logPath);
        if (empty($logs)) {
            return 'Plik logów jest pusty.';
        }
        
        // Konwertuj logi do formatu HTML
        $logs = explode("\n", $logs);
        $logs = array_filter($logs);
        $logs = array_slice($logs, -50); // Ostatnie 50 wpisów
        
        $html = '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .log-entry { 
                margin-bottom: 10px; 
                padding: 10px; 
                border: 1px solid #ddd; 
                border-radius: 4px;
            }
            .error { background-color: #ffebee; }
            .info { background-color: #e3f2fd; }
            pre { white-space: pre-wrap; margin: 0; }
        </style>';
        $html .= '</head><body>';
        $html .= '<h2>Ostatnie logi SMS-ów</h2>';
        
        foreach ($logs as $log) {
            $class = (strpos($log, 'ERROR') !== false) ? 'error' : 'info';
            $html .= "<div class='log-entry {$class}'><pre>" . htmlspecialchars($log) . "</pre></div>";
        }
        
        $html .= '</body></html>';
        
        return $html;
        
    } catch (\Exception $e) {
        return 'Błąd podczas odczytu logów: ' . $e->getMessage();
    }
});

Route::get('/sms-monitor', function () {
    return view('sms-monitor');
});

Route::get('/sms-logs-data', function () {
    // Odczytaj ostatnie wpisy z logów
    $logs = file_get_contents(storage_path('logs/laravel.log'));
    $logs = explode("\n", $logs);
    $logs = array_filter($logs);
    $logs = array_slice($logs, -50); // Ostatnie 50 wpisów
    
    $filtered_logs = [];
    foreach ($logs as $log) {
        if (strpos($log, 'Webhook received') !== false) {
            $filtered_logs[] = htmlspecialchars($log);
        }
    }
    
    return response()->json(['logs' => implode('<br>', $filtered_logs)]);
});

Route::get('/sms-list', [SmsWebhookController::class, 'index']);

Route::get('/configure-webhook-site', function () {
    // Użyj webhook.site dla łatwej weryfikacji
    $webhookSiteUrl = 'https://webhook.site/6a76d9d1-9f8b-404a-aaa0-3af41c7c8b23';
    // Możesz użyć własnego URL webhook.site
    
    return '<form method="POST" action="/send-sms-config">
        <input type="hidden" name="_token" value="' . csrf_token() . '">
        <label>URL Laravel (gdzie SMS Gateway ma wysyłać dane):</label>
        <input type="text" name="url" value="' . $webhookSiteUrl . '" style="width: 300px;"><br><br>
        <label>ID (identyfikator konfiguracji):</label>
        <input type="text" name="id" value="infoSMS"><br><br>
        <label>Event (typ zdarzenia):</label>
        <input type="text" name="event" value="sms:received"><br><br>
        <button type="submit">Skonfiguruj SMS Gateway</button>
    </form>';
});

Route::post('/send-sms-config', function (Request $request) {
    try {
        Log::info('Otrzymano konfigurację SMS Gateway:', $request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Konfiguracja została zapisana pomyślnie',
            'data' => $request->all()
        ], 201);
    } catch (\Exception $e) {
        Log::error('Błąd podczas zapisywania konfiguracji:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Wystąpił błąd: ' . $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/test-sms-webhook', function () {
    try {
        // Tworzenie przykładowego payloadu SMS
        $testPayload = [
            'deviceId' => 'test-device',
            'event' => 'sms:received',
            'id' => 'test-id',
            'payload' => [
                'message' => 'Test wiadomości SMS',
                'receivedAt' => now()->format('Y-m-d\TH:i:s.000\Z'),
                'messageId' => 'test-message-id',
                'phoneNumber' => '+48123456789',
                'simNumber' => 1
            ],
            'webhookId' => 'infoSMS'
        ];
        
        // Zamiast wysyłać żądanie HTTP, użyjmy bezpośrednio kontrolera
        $controller = new \App\Http\Controllers\SmsWebhookController();
        $request = new \Illuminate\Http\Request();
        $request->merge($testPayload);
        
        $response = $controller->handle($request);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Test SMS został zapisany',
            'response' => $response->getData()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/configure-sms-gateway', function () {
    return view('configure-sms-gateway');
});

Route::get('/drop-table', function() {
    Schema::dropIfExists('sms_messages');
    return "Tabela usunięta";
});

Route::get('/test-direct-save', function () {
    try {
        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => 'test-device',
            'message_id' => 'test-' . time(),
            'phone_number' => '+48123456789',
            'message' => 'Test bezpośredniego zapisu',
            'received_at' => now(),
            'sim_number' => 1,
            'webhook_id' => 'test',
            'raw_payload' => ['test' => true]
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany pomyślnie',
            'id' => $smsMessage->id
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-connection', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Połączenie działa!',
        'time' => now()->format('Y-m-d H:i:s')
    ]);
});

Route::get('/check-webhook-format', function() {
    // Przykładowy format z webhook.site
    $testData = file_get_contents('https://webhook.site/token/6a76d9d1-9f8b-404a-aaa0-3af41c7c8b23/request/latest/raw');
    
    return response()->json([
        'webhook_data' => json_decode($testData, true),
        'formatted_data' => [
            'example' => 'To jest format, który Laravel oczekuje'
        ]
    ]);
});

Route::get('/get-webhook-url', function() {
    $url = 'http://192.168.1.115:8000/webhook';
    return response()->json([
        'webhook_url' => $url,
        'test_url' => $url . '/test',
        'instructions' => 'Użyj tego URL w aplikacji SMS Gateway',
        'format' => [
            'deviceId' => 'ID urządzenia',
            'message' => 'Treść SMS',
            'phoneNumber' => 'Numer telefonu',
            'receivedAt' => 'Data otrzymania'
        ]
    ]);
});

Route::get('/test-real-sms', function () {
    $testPayload = [
        "deviceId" => "ffffffff9496f2ca00000195ed2bb800",
        "event" => "sms:received",
        "id" => "RNZqtZddgi7FWL6Cl43wn",
        "payload" => [
            "message" => "Test SMS",
            "receivedAt" => now()->format('Y-m-d\TH:i:s.000P'),
            "messageId" => "5d266bc0",
            "phoneNumber" => "+48504399970",
            "simNumber" => 2
        ],
        "webhookId" => "infoSMS"
    ];

    try {
        $controller = new \App\Http\Controllers\SmsWebhookController();
        $request = new \Illuminate\Http\Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->initialize([], [], [], [], [], [], json_encode($testPayload));

        $response = $controller->handle($request);

        return response()->json([
            'status' => 'success',
            'test_payload' => $testPayload,
            'response' => $response->getData()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/debug-webhook', function (Request $request) {
    $data = [
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'json' => $request->json()->all(),
        'all' => $request->all(),
        'ip' => $request->ip(),
        'url' => $request->fullUrl()
    ];
    
    Log::info('Debug webhook:', $data);
    
    return response()->json([
        'status' => 'success',
        'received_data' => $data
    ]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/debug-webhook-test', function () {
    return view('debug-webhook-test');
});

// Dodaj trasę dla debugowania
Route::any('/webhook-debug', function (Request $request) {
    return response()->json([
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'all' => $request->all()
    ]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/test-webhook-form', function () {
    return view('test-webhook-form');
});

Route::get('/test-phone-connection', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Połączenie z telefonu działa!',
        'time' => now()->format('Y-m-d H:i:s'),
        'your_ip' => request()->ip()
    ]);
});

Route::post('/sms-gateway-debug', function (Request $request) {
    $logFile = storage_path('logs/sms-gateway-debug.log');
    $data = [
        'time' => now()->format('Y-m-d H:i:s'),
        'ip' => $request->ip(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'all' => $request->all()
    ];
    
    file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    return response()->json([
        'status' => 'debug_success',
        'received' => $data
    ]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Dodaj nową trasę dla formularza testowego
Route::get('/test-sms-gateway', function () {
    return view('test-sms-gateway');
});

// Dodaj trasę API dla listy SMS-ów
Route::get('/api/sms-list', function (Request $request) {
    $limit = $request->query('limit', 10); // Domyślnie 10, ale można zwiększyć przez parametr limit
    
    $messages = \App\Models\SmsMessage::orderBy('id', 'desc')
        ->limit($limit)
        ->get();
    
    return response()->json([
        'messages' => $messages
    ]);
});

Route::post('/sms-debug', function (Request $request) {
    // Loguj wszystko do pliku
    $logFile = storage_path('logs/sms-raw.log');
    $data = [
        'time' => now()->format('Y-m-d H:i:s'),
        'ip' => $request->ip(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'query' => $request->query(),
        'request' => $request->all()
    ];
    
    file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // Próbuj zapisać SMS
    try {
        $content = $request->getContent();
        Log::info('Otrzymano dane:', ['content' => $content]);
        
        $data = json_decode($content, true) ?? $request->all();
        
        // Sprawdź strukturę danych
        Log::info('Sparsowane dane:', ['data' => $data]);
        
        $smsData = $data['payload'] ?? $data;
        
        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => $data['deviceId'] ?? null,
            'message_id' => $smsData['messageId'] ?? null,
            'phone_number' => $smsData['phoneNumber'] ?? '',
            'message' => $smsData['message'] ?? '',
            'received_at' => now(),
            'sim_number' => $smsData['simNumber'] ?? null,
            'webhook_id' => $data['webhookId'] ?? null,
            'raw_payload' => $data
        ]);
        
        Log::info('SMS zapisany:', ['id' => $smsMessage->id]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany pomyślnie',
            'id' => $smsMessage->id,
            'received_data' => $data
        ]);
        
    } catch (\Exception $e) {
        Log::error('Błąd podczas zapisywania SMS:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'received_data' => $data ?? null
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/test-gateway', function () {
    return view('sms-gateway-test');
});

// Dodaj nową trasę dla SMS Gateway z IP 192.168.1.86
Route::post('/sms-incoming', function (Request $request) {
    Log::info('SMS Gateway - Nowe żądanie z IP: ' . $request->ip(), [
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'all' => $request->all()
    ]);

    try {
        $content = $request->getContent();
        $data = json_decode($content, true) ?? $request->all();
        
        // Zapisz surowe dane do debugowania
        $logFile = storage_path('logs/sms-raw.log');
        file_put_contents($logFile, json_encode([
            'time' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'data' => $data
        ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

        // Próba zapisania SMS-a
        $smsData = $data['payload'] ?? $data;
        
        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => $data['deviceId'] ?? null,
            'message_id' => $smsData['messageId'] ?? null,
            'phone_number' => $smsData['phoneNumber'] ?? '',
            'message' => $smsData['message'] ?? '',
            'received_at' => now(),
            'sim_number' => $smsData['simNumber'] ?? null,
            'webhook_id' => $data['webhookId'] ?? null,
            'raw_payload' => $data
        ]);

        Log::info('SMS Gateway - SMS zapisany:', [
            'id' => $smsMessage->id,
            'message' => $smsMessage->message
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('SMS Gateway - Błąd:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->middleware(['throttle:60,1'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Dodaj nowy, uproszczony endpoint dla SMS Gateway
Route::post('/sms-receive', function (Request $request) {
    Log::info('Nowy SMS:', [
        'ip' => $request->ip(),
        'data' => $request->all()
    ]);

    try {
        $data = $request->all();
        
        // Zapisz surowe dane do pliku
        $logFile = storage_path('logs/sms-raw.log');
        file_put_contents($logFile, json_encode([
            'time' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'data' => $data
        ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

        // Sprawdź czy mamy payload
        if (!isset($data['payload'])) {
            throw new \Exception('Brak sekcji payload w danych');
        }

        $smsData = $data['payload'];
        
        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => $data['deviceId'] ?? null,
            'message_id' => $smsData['messageId'] ?? null,
            'phone_number' => $smsData['phoneNumber'] ?? '',
            'message' => $smsData['message'] ?? '',
            'received_at' => $smsData['receivedAt'] ?? now(),
            'sim_number' => $smsData['simNumber'] ?? null,
            'webhook_id' => $data['webhookId'] ?? null,
            'raw_payload' => $data
        ]);

        Log::info('SMS zapisany:', [
            'id' => $smsMessage->id,
            'message' => $smsMessage->message
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('Błąd podczas zapisywania SMS:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/sms-gateway-setup', function () {
    return view('sms-gateway-setup');
});

Route::post('/sms-webhook-receive', function (Request $request) {
    Log::info('Otrzymano SMS z webhook.site:', [
        'ip' => $request->ip(),
        'data' => $request->all()
    ]);

    try {
        $data = $request->all();
        
        // Zapisz surowe dane do debugowania
        $logFile = storage_path('logs/sms-raw.log');
        file_put_contents($logFile, json_encode([
            'time' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'data' => $data
        ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

        // Sprawdź czy mamy payload
        if (!isset($data['payload'])) {
            throw new \Exception('Brak sekcji payload w danych');
        }

        $smsData = $data['payload'];
        
        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => $data['deviceId'] ?? null,
            'message_id' => $smsData['messageId'] ?? null,
            'phone_number' => $smsData['phoneNumber'] ?? '',
            'message' => $smsData['message'] ?? '',
            'received_at' => $smsData['receivedAt'] ?? now(),
            'sim_number' => $smsData['simNumber'] ?? null,
            'webhook_id' => $data['webhookId'] ?? null,
            'raw_payload' => $data
        ]);

        Log::info('SMS zapisany:', [
            'id' => $smsMessage->id,
            'message' => $smsMessage->message
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('Błąd podczas zapisywania SMS:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/test-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'csrf_field' => csrf_field(),
        'session_status' => session()->isStarted() ? 'started' : 'not started',
        'middleware' => app()->make(\Illuminate\Routing\Router::class)->getRoutes()->getByName('test-csrf')->gatherMiddleware()
    ]);
})->name('test-csrf');

require __DIR__.'/auth.php';

Route::post('/sms-api', function (Request $request) {
    // ... ten sam kod, co w trasie głównej "/"
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/sms', function (Request $request) {
    // ... ten sam kod, co w trasie głównej "/"
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/manual-webhook', function (Request $request) {
    Log::info('Manual Webhook - Nowe żądanie', [
        'ip' => $request->ip(),
        'method' => $request->method(),
        'content' => $request->getContent()
    ]);

    try {
        $content = $request->getContent();
        $data = json_decode($content, true) ?? $request->all();
        
        // Zapisz surowe dane do debugowania
        $logFile = storage_path('logs/manual-webhook.log');
        file_put_contents($logFile, json_encode([
            'time' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'data' => $data
        ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

        // Sprawdź czy mamy payload
        if (!isset($data['payload'])) {
            Log::warning('Otrzymano dane bez payloadu:', ['data' => $data]);
            // Próbujemy traktować całe dane jako payload
            $smsData = $data;
        } else {
            $smsData = $data['payload'];
        }
        
        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => $data['deviceId'] ?? null,
            'message_id' => $smsData['messageId'] ?? null,
            'phone_number' => $smsData['phoneNumber'] ?? '',
            'message' => $smsData['message'] ?? '',
            'received_at' => now(),
            'sim_number' => $smsData['simNumber'] ?? null,
            'webhook_id' => $data['webhookId'] ?? null,
            'raw_payload' => $data
        ]);

        Log::info('Manual Webhook - SMS zapisany:', [
            'id' => $smsMessage->id,
            'message' => $smsMessage->message
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('Manual Webhook - Błąd:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/webhook-copy', function (Request $request) {
    Log::info('Webhook Copy - Nowe żądanie', [
        'ip' => $request->ip(),
        'content' => $request->getContent()
    ]);

    try {
        $content = $request->getContent();
        $data = json_decode($content, true) ?? $request->all();
        
        // Zapisz surowe dane do debugowania
        $logFile = storage_path('logs/webhook-copy.log');
        file_put_contents($logFile, json_encode([
            'time' => now()->format('Y-m-d H:i:s'),
            'data' => $data
        ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

        $smsMessage = \App\Models\SmsMessage::create([
            'device_id' => $data['deviceId'] ?? null,
            'message_id' => $data['messageId'] ?? null,
            'phone_number' => $data['phoneNumber'] ?? '',
            'message' => $data['message'] ?? '',
            'received_at' => now(),
            'sim_number' => $data['simNumber'] ?? null,
            'webhook_id' => $data['webhookId'] ?? null,
            'raw_payload' => $data
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS skopiowany z webhook.site',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('Webhook Copy - Błąd:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/webhook-copy', function () {
    return view('webhook-copy');
});

Route::get('/webhook-copy-api', function () {
    return view('webhook-copy-api');
});

Route::get('/webhook-logs', function () {
    try {
        $logPath = storage_path('logs/webhook-detailed.log');
        
        if (!file_exists($logPath)) {
            return 'Plik logów nie istnieje.';
        }
        
        $logs = file_get_contents($logPath);
        if (empty($logs)) {
            return 'Plik logów jest pusty.';
        }
        
        $html = '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .log-entry { 
                margin-bottom: 20px; 
                padding: 10px; 
                border: 1px solid #ddd; 
                border-radius: 4px;
                background-color: #f8f9fa;
            }
            pre { white-space: pre-wrap; margin: 0; }
            h2 { margin-bottom: 20px; }
            .refresh { padding: 10px; margin-bottom: 20px; }
        </style>';
        $html .= '</head><body>';
        $html .= '<h2>Logi webhooków</h2>';
        $html .= '<button class="refresh" onclick="location.reload()">Odśwież</button>';
        
        // Dzielimy logi na wpisy
        $entries = explode("\n\n", $logs);
        $entries = array_filter($entries);
        $entries = array_slice($entries, -10); // Ostatnie 10 wpisów
        
        foreach ($entries as $entry) {
            $html .= "<div class='log-entry'><pre>" . htmlspecialchars($entry) . "</pre></div>";
        }
        
        $html .= '</body></html>';
        
        return $html;
        
    } catch (\Exception $e) {
        return 'Błąd podczas odczytu logów: ' . $e->getMessage();
    }
});

Route::get('/laravel-logs', function () {
    try {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return 'Plik logów Laravel nie istnieje.';
        }
        
        $logs = file_get_contents($logPath);
        
        $html = '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            pre { white-space: pre-wrap; margin: 0; background-color: #f5f5f5; padding: 10px; border-radius: 4px; }
            h2 { margin-bottom: 20px; }
            .refresh { padding: 10px; margin-bottom: 20px; }
        </style>';
        $html .= '</head><body>';
        $html .= '<h2>Logi Laravel</h2>';
        $html .= '<button class="refresh" onclick="location.reload()">Odśwież</button>';
        $html .= '<pre>' . htmlspecialchars($logs) . '</pre>';
        $html .= '</body></html>';
        
        return $html;
        
    } catch (\Exception $e) {
        return 'Błąd podczas odczytu logów: ' . $e->getMessage();
    }
});

Route::get('/api/last-sms', function () {
    try {
        $lastSms = \App\Models\SmsMessage::latest()->first();
        
        if ($lastSms) {
            return response()->json([
                'status' => 'success',
                'message' => 'Ostatni SMS',
                'sms' => [
                    'id' => $lastSms->id,
                    'message' => $lastSms->message,
                    'phone_number' => $lastSms->phone_number,
                    'received_at' => $lastSms->received_at,
                    'sim_number' => $lastSms->sim_number
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'info',
                'message' => 'Brak SMS-ów w bazie danych'
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/sms-gateway-webhook-2025', function (Request $request) {
    // Używamy standardowego systemu logowania Laravel
    Log::info('SMS Gateway 2025 - Nowe żądanie', [
        'ip' => $request->ip(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent()
    ]);
    
    try {
        $content = $request->getContent();
        $data = json_decode($content, true);
        
        if ($data) {
            Log::info('Parsed webhook data', ['data' => $data]);
            
            if (isset($data['payload'])) {
                $smsData = $data['payload'];
                
                $smsMessage = \App\Models\SmsMessage::create([
                    'device_id' => $data['deviceId'] ?? null,
                    'message_id' => $smsData['messageId'] ?? null,
                    'phone_number' => $smsData['phoneNumber'] ?? '',
                    'message' => $smsData['message'] ?? '',
                    'received_at' => now(),
                    'sim_number' => $smsData['simNumber'] ?? null,
                    'webhook_id' => $data['webhookId'] ?? null,
                    'raw_payload' => json_encode($data)
                ]);
                
                Log::info('SMS saved successfully', [
                    'id' => $smsMessage->id,
                    'message' => $smsData['message'] ?? ''
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS zapisany',
                    'id' => $smsMessage->id
                ]);
            }
        }
        
        Log::warning('Invalid webhook data format', [
            'content' => $content
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Nieprawidłowy format danych'
        ], 400);
        
    } catch (\Exception $e) {
        Log::error('Webhook error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->middleware(['api'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Dodaj nową trasę z wyraźnym wyłączeniem middleware web
Route::any('/sms-webhook-direct', function (Request $request) {
    // Ten sam kod co w trasie '/'
})->withoutMiddleware(['web'])->middleware(['api']);

// Dodaj tę trasę po istniejących trasach dotyczących dashboard
Route::get('/dashboard/sms-monitor', function () {
    return view('dashboard.sms-monitor');
})->middleware(['auth'])->name('dashboard.sms-monitor');

// Dodaj tę trasę obok istniejącej trasy dashboard/sms-monitor
Route::get('/dashboard/sms-list', function () {
    return view('dashboard.sms-list');
})->middleware(['auth'])->name('dashboard.sms-list');

// Dodaj tę trasę obok istniejących tras dashboard
Route::get('/dashboard/logs', function () {
    return view('dashboard.logs');
})->middleware(['auth'])->name('dashboard.logs');

// Endpoint do pobierania logów
Route::get('/api/logs', function () {
    $logPath = storage_path('logs/laravel.log');
    
    if (!file_exists($logPath)) {
        return response('Plik logów nie istnieje.', 404);
    }
    
    $logs = file_get_contents($logPath);
    return response($logs);
});

// Endpoint do czyszczenia logów
Route::post('/api/logs/clear', function () {
    $logPath = storage_path('logs/laravel.log');
    
    if (file_exists($logPath)) {
        file_put_contents($logPath, '');
        return response()->json(['status' => 'success', 'message' => 'Logi zostały wyczyszczone']);
    }
    
    return response()->json(['status' => 'error', 'message' => 'Plik logów nie istnieje'], 404);
})->middleware('auth');

// Endpoint do usuwania SMS-a
Route::delete('/api/sms/{id}', function ($id) {
    try {
        $sms = \App\Models\SmsMessage::findOrFail($id);
        
        // Zapisz informacje o SMS-ie przed usunięciem
        $smsData = [
            'id' => $sms->id,
            'phone_number' => $sms->phone_number,
            'message' => $sms->message,
            'received_at' => $sms->received_at,
        ];
        
        // Zapisz informacje o użytkowniku wykonującym operację
        $user = auth()->user() ? auth()->user()->id : 'niezalogowany';
        
        // Usuń SMS-a
        $sms->delete();
        
        // Dodaj log o usunięciu SMS-a
        Log::info('SMS został usunięty', [
            'sms' => $smsData,
            'user' => $user,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'SMS został usunięty'
        ]);
    } catch (\Exception $e) {
        // Dodaj tę linię
        $user = auth()->user() ? auth()->user()->id : 'niezalogowany';
        
        // Loguj błąd
        Log::error('Błąd podczas usuwania SMS-a', [
            'id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user' => $user,
            'ip' => request()->ip()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->middleware('auth');

// Dodaj nowy endpoint testowy
Route::any('/webhook-test', function (Request $request) {
    $log_data = [
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'query' => $request->query(),
        'body' => $request->all(),
        'raw' => $request->getContent(),
        'ip' => $request->ip(),
        'time' => now()->toDateTimeString()
    ];
    
    Log::channel('daily')->info('Webhook test received', $log_data);
    
    // Zapisz również do pliku dla łatwiejszego dostępu
    file_put_contents(
        storage_path('logs/webhook-test-' . date('Y-m-d-H-i-s') . '.json'),
        json_encode($log_data, JSON_PRETTY_PRINT)
    );
    
    return response()->json([
        'status' => 'success',
        'message' => 'Webhook test received and logged',
        'data' => $log_data
    ]);
});

Route::get('/test-sms-endpoint', function () {
    return view('test-sms-form');
});

Route::any('/sms-direct', function (Request $request) {
    // Przekierowanie żądania do API
    return app()->call('App\Http\Controllers\ApiController@handleSmsDirectWebhook', ['request' => $request]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
