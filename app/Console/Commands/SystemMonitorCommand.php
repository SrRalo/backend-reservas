<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SystemMonitorService;

class SystemMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:monitor {--interval=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system stats and broadcast via WebSocket';

    protected $monitorService;

    public function __construct(SystemMonitorService $monitorService)
    {
        parent::__construct();
        $this->monitorService = $monitorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        
        $this->info("Starting system monitor with {$interval} seconds interval...");
        
        while (true) {
            try {
                $this->monitorService->broadcastSystemStats();
                $this->info('System stats broadcasted at ' . now()->toTimeString());
                
                sleep($interval);
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                sleep(5); // Wait 5 seconds before retry
            }
        }
    }
}
