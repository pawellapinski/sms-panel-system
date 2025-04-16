<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    /**
     * Pobierz ostatni SMS
     */
    public function getLastSms()
    {
        $lastSms = SmsMessage::latest('received_at')->first();

        // Logowanie pobrania ostatniego SMS-a
        Log::info('Pobrano ostatni SMS', [
            'user_id' => auth()->id() ?? 'guest',
            'user_ip' => request()->ip(),
            'sms_exists' => $lastSms ? true : false,
            'time' => now()->format('Y-m-d H:i:s')
        ]);

        if ($lastSms) {
            return response()->json([
                'status' => 'success',
                'sms' => $lastSms
            ]);
        } else {
            return response()->json([
                'status' => 'error!!!!',
                'message' => 'Brak SMS-ów w bazie danych'
            ]);
        }
    }

    /**
     * Pobierz listę SMS-ów
     */
    public function getSmsList(Request $request)
    {
        $limit = $request->input('limit', 100);
        $page = $request->input('page', 1);

        $total = SmsMessage::count();
        $totalPages = ceil($total / $limit);

        $messages = SmsMessage::orderBy('received_at', 'desc')
                            ->skip(($page - 1) * $limit)
                            ->take($limit)
                            ->get();

        // Logowanie pobrania listy SMS-ów
        Log::info('Pobrano listę SMS-ów', [
            'user_id' => auth()->id() ?? 'guest',
            'user_ip' => request()->ip(),
            'page' => $page,
            'limit' => $limit,
            'total_records' => $total,
            'time' => now()->format('Y-m-d H:i:s')
        ]);

        return response()->json([
            'status' => 'success',
            'messages' => $messages,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]);
    }

    /**
     * Usuń SMS o podanym ID
     */
    public function destroy($id)
    {
        try {
            $sms = SmsMessage::findOrFail($id);

            // Zapisz informacje o SMS-ie przed usunięciem
            $smsData = [
                'id' => $sms->id,
                'phone_number' => $sms->phone_number,
                'message' => $sms->message,
                'received_at' => $sms->received_at,
                'user_id' => auth()->id(),
                'user_ip' => request()->ip(),
                'time' => now()->format('Y-m-d H:i:s')
            ];

            // Usuń SMS
            $sms->delete();

            // Zaloguj usunięcie
            Log::info('SMS został usunięty', $smsData);

            return response()->json(['status' => 'success', 'message' => 'SMS został usunięty']);
        } catch (\Exception $e) {
            // Logowanie błędu
            Log::error('Błąd podczas usuwania SMS-a', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'user_ip' => request()->ip(),
                'time' => now()->format('Y-m-d H:i:s')
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Usuń wszystkie SMS-y z bazy danych
     */
    public function clearAll()
    {
        try {
            // Zapisz informację o ilości usuniętych SMS-ów
            $count = SmsMessage::count();

            // Usuń wszystkie SMS-y
            SmsMessage::truncate();

            // Zaloguj usunięcie
            Log::info('Wszystkie SMS-y zostały usunięte', [
                'count' => $count,
                'time' => now()->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Wszystkie SMS-y zostały usunięte',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Błąd podczas usuwania SMS-ów: ' . $e->getMessage()
            ], 500);
        }
    }
}
