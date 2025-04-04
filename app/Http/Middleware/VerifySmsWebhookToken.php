<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifySmsWebhookToken
{
    public function handle(Request $request, Closure $next)
    {
        $validToken = config('services.sms_gateway.token');
        
        if (empty($validToken) || $request->header('X-SMS-Gateway-Token') !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
} 