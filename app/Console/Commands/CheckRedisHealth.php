<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CheckRedisHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'afm:check-redis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check connectivity to the Redis server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking Redis connection...');

        try {
            $start = microtime(true);
            Redis::set('afm_health_check', 'ok');
            $value = Redis::get('afm_health_check');
            Redis::del('afm_health_check');
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($value === 'ok') {
                $this->info("Redis is ONLINE. Response time: {$duration}ms");
                return 0;
            } else {
                $this->error('Redis is ONLINE but returned unexpected value.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Redis is OFFLINE. Error: " . $e->getMessage());
            Log::channel('ssotoken')->error('Redis Health Check Failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }
}
