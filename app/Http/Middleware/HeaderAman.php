<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HeaderAman
{
    public function handle(Request $r, Closure $next)
    {
        $res = $next($r);
        $res->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $res->headers->set('X-Content-Type-Options', 'nosniff');
        $res->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        return $res;
    }
}
