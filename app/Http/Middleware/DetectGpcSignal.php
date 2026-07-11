<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Flags requests carrying a Global Privacy Control signal (Sec-GPC: 1).
 * Eleven+ state privacy laws (incl. the Colorado Privacy Act) require
 * treating this as a valid opt-out of analytics/advertising data sharing.
 */
class DetectGpcSignal
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers->get('Sec-GPC') === '1') {
            $request->attributes->set('gpc', true);
        }

        return $next($request);
    }
}
