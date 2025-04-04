<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/sms-direct',
        '/webhook',
        '/sms-webhook',
        '/sms-receive',
        '/sms-incoming',
        '/sms-webhook-receive',
        '/sms-gateway-webhook-2025',
        '/webhook-test',
        '/sms-api',
        '/sms',
        'debug-webhook',
        '/webhook-debug',
        '/webhook-copy',
        '*' // Tymczasowo wyłącz CSRF dla wszystkich ścieżek
    ];
} 