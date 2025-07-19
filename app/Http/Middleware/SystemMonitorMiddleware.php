<?php

namespace App\Http\Middleware;

use Closure;
use App\Events\SystemMonitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SystemMonitorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Broadcast inicio de request
        broadcast(new SystemMonitorEvent('api', 'request_start', [
            'method' => $request->method(),
            'url' => $request->url(),
            'user_id' => Auth::check() ? Auth::user()->id : null,
            'ip' => $request->ip(),
        ]));

        $response = $next($request);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // en milisegundos

        // Broadcast fin de request
        broadcast(new SystemMonitorEvent('api', 'request_end', [
            'method' => $request->method(),
            'url' => $request->url(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'memory_usage' => memory_get_peak_usage(true),
        ]));

        return $response;
    }
}
