<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesQueueConsumption;
use Tests\TestCase;

class QueueHeartbeatCommandTest extends TestCase
{
    use FakesQueueConsumption;

    private const PING_URL = 'https://hc-ping.com/00000000-0000-4000-8000-000000000000';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.healthchecks.queue_url', self::PING_URL);
    }

    #[Test]
    public function it_pings_while_the_queue_is_consumed(): void
    {
        Http::fake([self::PING_URL => Http::response('OK')]);

        $this->workerReports('running');
        $this->waitsFor(4);

        $this->artisan('race:queue-heartbeat')->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->url() === self::PING_URL);
    }

    #[Test]
    public function it_stays_silent_while_the_worker_is_absent(): void
    {
        Http::fake();

        $this->workerReports();

        $this->artisan('race:queue-heartbeat')
            ->expectsOutputToContain('worker-absent')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_stays_silent_while_the_queue_is_backlogged(): void
    {
        Http::fake();

        $this->workerReports('running');
        $this->waitsFor(3600);

        $this->artisan('race:queue-heartbeat')
            ->expectsOutputToContain('backlogged')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_says_so_when_no_heartbeat_is_configured(): void
    {
        Http::fake();

        config()->set('services.healthchecks.queue_url', null);

        $this->artisan('race:queue-heartbeat')
            ->expectsOutputToContain('HEALTHCHECKS_QUEUE_URL')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    /** An unusable URL reads as an absent one; a stalled queue would otherwise never reach its check. */
    #[Test]
    public function it_says_so_when_the_configured_heartbeat_is_not_a_url(): void
    {
        Http::fake();

        config()->set('services.healthchecks.queue_url', 'hc-ping.com/no-scheme');

        $this->artisan('race:queue-heartbeat')
            ->expectsOutputToContain('HEALTHCHECKS_QUEUE_URL')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_fails_when_the_heartbeat_refuses_the_ping(): void
    {
        Http::fake([self::PING_URL => Http::response('nope', 500)]);

        $this->workerReports('running');
        $this->waitsFor(0);

        $this->artisan('race:queue-heartbeat')
            ->expectsOutputToContain('500')
            ->assertFailed();
    }
}
