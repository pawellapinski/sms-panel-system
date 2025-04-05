<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SmsMessage;

class SmsGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:60,1');
        $this->middleware('api');
    }

    public function receive(Request $request)
    {
        Log::info('SMS Gateway Controller - Nowe żądanie', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent()
        ]);

        try {
            $content = $request->getContent();
            $data = json_decode($content, true) ?? $request->all();

            // Logowanie szczegółów SMS-a
            Log::info('SMS Gateway - Odebrano nowy SMS', [
                'time' => now()->format('Y-m-d H:i:s'),
                'ip' => $request->ip(),
                'data' => $data
            ]);

            // Sprawdź czy mamy payload
            if (!isset($data['payload'])) {
                Log::warning('Otrzymano dane bez payloadu:', ['data' => $data]);
                // Próbujemy traktować całe dane jako payload
                $smsData = $data;
            } else {
                $smsData = $data['payload'];
            }

            $smsMessage = SmsMessage::create([
                'device_id' => $data['deviceId'] ?? null,
                'message_id' => $smsData['messageId'] ?? null,
                'phone_number' => $smsData['phoneNumber'] ?? '',
                'message' => $smsData['message'] ?? '',
                'received_at' => now(),
                'sim_number' => $smsData['simNumber'] ?? null,
                'webhook_id' => $data['webhookId'] ?? null,
                'raw_payload' => $data
            ]);

            Log::info('SMS Gateway Controller - SMS zapisany:', [
                'id' => $smsMessage->id,
                'message' => $smsMessage->message
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'SMS zapisany',
                'id' => $smsMessage->id
            ], 201);

        } catch (\Exception $e) {
            Log::error('SMS Gateway Controller - Błąd:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
