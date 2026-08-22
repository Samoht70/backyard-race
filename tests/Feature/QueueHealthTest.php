<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesQueueConsumption;
use Tests\TestCase;

class QueueHealthTest extends TestCase
{
    use FakesQueueConsumption;

    #[Test]
    public function it_reports_a_consuming_queue(): void
    {
        $this->workerReports('running');
        $this->waitsFor(12);

        $this->get('up/queue')
            ->assertOk()
            ->assertExactJson(['queue' => 'consuming']);
    }

    #[Test]
    public function it_refuses_when_no_worker_reports(): void
    {
        $this->workerReports();

        $this->get('up/queue')
            ->assertServiceUnavailable()
            ->assertExactJson(['queue' => 'worker-absent']);
    }

    #[Test]
    public function it_refuses_when_the_worker_is_paused(): void
    {
        $this->workerReports('paused');

        $this->get('up/queue')
            ->assertServiceUnavailable()
            ->assertExactJson(['queue' => 'worker-paused']);
    }

    #[Test]
    public function it_refuses_when_the_wait_passes_the_configured_threshold(): void
    {
        $this->workerReports('running');
        $this->waitsFor(config('horizon.waits.redis:default') + 1);

        $this->get('up/queue')
            ->assertServiceUnavailable()
            ->assertExactJson(['queue' => 'backlogged']);
    }

    #[Test]
    public function it_holds_a_queue_silenced_by_a_zero_threshold(): void
    {
        config()->set('horizon.waits.redis:default', 0);

        $this->workerReports('running');
        $this->waitsFor(3600);

        $this->get('up/queue')->assertOk();
    }

    /** The two probes are what tells a dead worker apart from a dead application. */
    #[Test]
    public function it_leaves_the_application_probe_healthy_while_the_queue_is_stalled(): void
    {
        $this->workerReports();

        $this->get('up/queue')->assertServiceUnavailable();
        $this->get('up')->assertOk();
    }

    #[Test]
    public function it_answers_without_a_session(): void
    {
        $this->workerReports('running');
        $this->waitsFor(0);

        $this->get('up/queue')->assertCookieMissing(config('session.cookie'));
    }
}
