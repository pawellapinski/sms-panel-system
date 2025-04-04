<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SmsMessage;

class ApiController extends Controller
{
    public function handleSmsDirectWebhook(Request $request)
    {
        // Logowanie przychodzącego żądania
        Log::info('SMS Gateway - odebrano SMS przez /sms-direct (web route)', [
            'content' => $request->getContent(),
            'ip' => $request->ip(),
            'method' => $request->method()
        ]);
        
        try {
            // Parsowanie danych
            $content = $request->getContent();
            $data = json_decode($content, true) ?? $request->all();
            
            // Zapisz surowe dane do debugowania
            $logFile = storage_path('logs/sms-direct-raw.log');
            file_put_contents($logFile, json_encode([
                'time' => now()->format('Y-m-d H:i:s'),
                'data' => $data
            ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
            
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
                
                Log::info('SMS zapisany pomyślnie (web route)', [
                    'id' => $sms->id,
                    'message' => $sms->message
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS zapisany pomyślnie',
                    'id' => $sms->id
                ]);
            } else {
                Log::warning('Niepoprawny format danych SMS (web route)', [
                    'data' => $data
                ]);
                
                // Zwracamy kod 200 aby SMS Gateway nie próbował ponownie
                return response()->json([
                    'status' => 'error',
                    'message' => 'Niepoprawny format danych'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Błąd podczas zapisywania SMS (web route)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Zwracamy kod 200 aby SMS Gateway nie próbował ponownie
            return response()->json([
                'status' => 'error',
                'message' => 'Wystąpił błąd: ' . $e->getMessage()
            ]);
        }
    }
} 