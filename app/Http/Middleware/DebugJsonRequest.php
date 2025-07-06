<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugJsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('Debug JSON Request', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
            'is_json' => $request->isJson(),
            'json_all' => $request->json() ? $request->json()->all() : null,
            'input_all' => $request->all(),
        ]);
        
        return $next($request);
    }
}
