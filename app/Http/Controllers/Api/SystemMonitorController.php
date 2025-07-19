<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemMonitorService;
use App\Events\SystemMonitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemMonitorController extends Controller
{
    protected $monitorService;

    public function __construct(SystemMonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

    public function getSystemStats()
    {
        $stats = $this->monitorService->broadcastSystemStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function testWebSocket(Request $request)
    {
        $message = $request->input('message', 'Test message from API');
        
        broadcast(new SystemMonitorEvent('test', 'message', [
            'message' => $message,
            'from' => 'api_test',
            'user_id' => Auth::check() ? Auth::user()->id : null,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'WebSocket test message sent',
        ]);
    }
}
