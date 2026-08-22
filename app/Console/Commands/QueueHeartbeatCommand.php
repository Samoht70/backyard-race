<?php

namespace App\Console\Commands;

use App\Services\QueueHealth\QueueConsumption;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class QueueHeartbeatCommand extends Command
{
    private const TIMEOUT_SECONDS = 10;

    private const ATTEMPTS = 3;

    private const BACKOFF_MILLISECONDS = 200;

    protected $signature = 'race:queue-heartbeat';

    protected $description = 'Ping the external heartbeat while the queue is consumed, so its silence carries the alarm';

    /**
     * @throws ConnectionException
     */
    public function handle(QueueConsumption $consumption): int
    {
        $url = $this->heartbeatUrl();

        if ($url === null) {
            $this->warn('No heartbeat configured: point `HEALTHCHECKS_QUEUE_URL` at a ping URL.');

            return self::SUCCESS;
        }

        $state = $consumption->state();

        if ($state->isStalled()) {
            $this->warn("Queue is {$state->value}: staying silent so the heartbeat expires.");

            return self::SUCCESS;
        }

        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->retry(self::ATTEMPTS, self::BACKOFF_MILLISECONDS, throw: false)
            ->get($url);

        if ($response->failed()) {
            $this->error("Heartbeat refused the ping with status {$response->status()}.");

            return self::FAILURE;
        }

        $this->info('Heartbeat sent.');

        return self::SUCCESS;
    }

    private function heartbeatUrl(): ?string
    {
        $configured = config('services.healthchecks.queue_url');

        if (! is_string($configured)) {
            return null;
        }

        return filter_var($configured, FILTER_VALIDATE_URL) === false ? null : $configured;
    }
}
