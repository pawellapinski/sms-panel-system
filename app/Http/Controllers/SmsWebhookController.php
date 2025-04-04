<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SmsWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Dodaj szczegółowe logowanie na początku metody
        Log::info('Webhook - Otrzymano żądanie:', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent(),
            'raw_payload' => $request->all()
        ]);

        try {
            // Próbuj różnych metod odczytu danych
            $content = $request->getContent();
            Log::info('Webhook - Surowa zawartość:', ['content' => $content]);
            
            // Parsuj JSON
            $payload = json_decode($content, true);
            Log::info('Webhook - Sparsowany JSON:', ['payload' => $payload]);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Błąd parsowania JSON: ' . json_last_error_msg());
            }

            // Sprawdź strukturę danych
            if (!isset($payload['payload'])) {
                throw new \Exception('Brak wymaganej sekcji payload');
            }

            $smsData = $payload['payload'];
            
            // Utwórz rekord SMS
            $smsMessage = SmsMessage::create([
                'device_id' => $payload['deviceId'] ?? null,
                'message_id' => $smsData['messageId'] ?? null,
                'phone_number' => $smsData['phoneNumber'] ?? '',
                'message' => $smsData['message'] ?? '',
                'received_at' => $smsData['receivedAt'] ?? now(),
                'sim_number' => $smsData['simNumber'] ?? null,
                'webhook_id' => $payload['webhookId'] ?? null,
                'raw_payload' => $payload
            ]);

            Log::info('Webhook - SMS zapisany:', [
                'id' => $smsMessage->id,
                'phone' => $smsData['phoneNumber'],
                'message' => $smsData['message']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'SMS zapisany pomyślnie',
                'id' => $smsMessage->id
            ], 201);

        } catch (\Exception $e) {
            Log::error('Webhook - Błąd:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function index()
    {
        $messages = SmsMessage::orderBy('received_at', 'desc')->paginate(10);
        return view('sms-list', compact('messages'));
    }
} 