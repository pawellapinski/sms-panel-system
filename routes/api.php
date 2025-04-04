<?php

use App\Http\Controllers\SmsWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SmsGatewayController;
use App\Models\SmsMessage;

// ... istniejące routy ...

// Endpoint dla webhooków SMS bez middleware
Route::post('/sms-webhook', function (Request $request) {
    // Ten sam kod co wcześniej, ale bez potrzeby wyłączania CSRF
});

Route::post('/webhook', [SmsWebhookController::class, 'handle']);

Route::post('/test-webhook', function (Request $request) {
    Log::info('Test webhook received', ['payload' => $request->all()]);
    return response()->json(['success' => true]);
});

// Ten plik nie ma domyślnie włączonej weryfikacji CSRF
Route::post('/sms-api', function (Request $request) {
    Log::info('SMS API - Nowe żądanie', [
        'ip' => $request->ip(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'content' => $request->getContent()
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

        Log::info('SMS API - SMS zapisany:', [
            'id' => $smsMessage->id,
            'message' => $smsMessage->message
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS zapisany',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('SMS API - Błąd:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/sms', [SmsGatewayController::class, 'receive']);

// Ten endpoint będzie obsługiwał przesyłanie SMS-ów przez API
Route::post('/webhook-copy', function (Request $request) {
    Log::info('Webhook Copy API - Nowe żądanie', [
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

        // Spróbujmy dopasować format danych z webhook.site
        if (isset($data['content'])) {
            $webhookData = json_decode($data['content'], true);
            if ($webhookData) {
                $data = $webhookData;
            }
        }

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

        return response()->json([
            'status' => 'success',
            'message' => 'SMS skopiowany z webhook.site',
            'id' => $smsMessage->id
        ], 201);

    } catch (\Exception $e) {
        Log::error('Webhook Copy API - Błąd:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Dodaj nowy endpoint dla SMS-ów
Route::any('/sms-direct', function (Request $request) {
    // Logowanie przychodzącego żądania
    Log::info('SMS Gateway - odebrano SMS przez /sms-direct', [
        'content' => $request->getContent(),
        'ip' => $request->ip(),
        'method' => $request->method()
    ]);

    try {
        // Parsowanie danych
        $content = $request->getContent();
        $data = json_decode($content, true) ?? $request->all();

        // Sprawdzanie struktury danych
        if (isset($data['payload']) && isset($data['event']) && $data['event'] === 'sms:received') {
            $smsData = $data['payload'];

            // Zapisywanie do bazy danych
            $sms = new SmsMessage();
            $sms->device_id = $data['deviceId'] ?? null;
            $sms->message_id = $smsData['messageId'] ?? null;
            $sms->phone_number = $smsData['phoneNumber'] ?? '';
            $sms->message = $smsData['message'] ?? '';
            $sms->received_at = $smsData['receivedAt'] ?? now();
            $sms->sim_number = $smsData['simNumber'] ?? null;
            $sms->webhook_id = $data['webhookId'] ?? null;
            $sms->raw_payload = json_encode($data);
            $sms->save();

            Log::info('SMS zapisany pomyślnie', [
                'id' => $sms->id,
                'message' => $sms->message
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'SMS zapisany pomyślnie',
                'id' => $sms->id
            ]);
        } else {
            Log::warning('Niepoprawny format danych SMS', [
                'data' => $data
            ]);

            // Zwracamy kod 200 aby SMS Gateway nie próbował ponownie
            return response()->json([
                'status' => 'error',
                'message' => 'Niepoprawny format danych'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Błąd podczas zapisywania SMS', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Zwracamy kod 200 aby SMS Gateway nie próbował ponownie
        return response()->json([
            'status' => 'error',
            'message' => 'Wystąpił błąd: ' . $e->getMessage()
        ]);
    }
});

// Trasa specjalnie dla SMS Gateway
Route::post('/sms-gateway-incoming', function (Request $request) {
    // Logowanie przychodzącego żądania
    Log::info('Otrzymano nowy SMS', [
        'payload' => $request->all(),
        'headers' => $request->header(),
    ]);

    try {
        // Sprawdzenie czy mamy poprawne dane
        if ($request->has('payload') &&
            $request->input('event') === 'sms:received') {

            $payload = $request->input('payload');

            // Zapisanie SMS-a do bazy danych
            $sms = new SmsMessage();
            $sms->message_id = $payload['messageId'] ?? null;
            $sms->phone_number = $payload['phoneNumber'] ?? null;
            $sms->message = $payload['message'] ?? null;
            $sms->sim_number = $payload['simNumber'] ?? null;
            $sms->received_at = $payload['receivedAt'] ?? now();
            $sms->raw_payload = json_encode($request->all());
            $sms->save();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS został odebrany i zapisany'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Nieprawidłowe dane wejściowe'
        ], 400);

    } catch (\Exception $e) {
        Log::error('Błąd przy przetwarzaniu SMS-a', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'payload' => $request->all()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Wystąpił błąd podczas przetwarzania SMS-a: ' . $e->getMessage()
        ], 500);
    }
})->name('api.sms.incoming');

Route::post('/sms-test', function (Request $request) {
    Log::info('Test SMS endpoint', [
        'payload' => $request->all(),
        'headers' => $request->header()
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Test endpoint działa poprawnie',
        'received' => $request->all()
    ]);
});

Route::post('/sms-direct-test', function (Request $request) {
    // Logowanie całego żądania dla celów diagnostycznych
    Log::info('Otrzymano testowy SMS na endpoint sms-direct-test', [
        'payload' => $request->all(),
        'headers' => $request->header()
    ]);

    try {
        // Sprawdzenie czy mamy poprawne dane
        if ($request->has('payload') && $request->input('event') === 'sms:received') {
            $payload = $request->input('payload');

            // Zapisanie SMS-a do bazy danych
            $sms = new SmsMessage();
            $sms->message_id = $payload['messageId'] ?? null;
            $sms->phone_number = $payload['phoneNumber'] ?? null;
            $sms->message = $payload['message'] ?? null;
            $sms->sim_number = $payload['simNumber'] ?? null;
            $sms->received_at = $payload['receivedAt'] ?? now();
            $sms->raw_payload = json_encode($request->all());
            $sms->save();

            Log::info('SMS zapisany pomyślnie', ['sms_id' => $sms->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'SMS został odebrany i zapisany'
            ]);
        } else {
            Log::warning('Nieprawidłowe dane SMS', ['request' => $request->all()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Nieprawidłowe dane wejściowe, ale żądanie zostało przyjęte'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Błąd przetwarzania SMS', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Wystąpił błąd, ale żądanie zostało przyjęte: ' . $e->getMessage()
        ]);
    }
});
