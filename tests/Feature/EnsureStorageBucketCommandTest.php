<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnsureStorageBucketCommandTest extends TestCase
{
    /** Continuous integration runs `composer setup` with no object storage reachable. */
    #[Test]
    public function it_provisions_nothing_when_the_media_disk_is_not_object_storage(): void
    {
        config()->set('media-library.disk_name', 'local');

        $this->artisan('storage:ensure-bucket')
            ->expectsOutputToContain('nothing to provision')
            ->assertSuccessful();
    }

    #[Test]
    public function it_provisions_nothing_when_the_media_disk_is_unknown(): void
    {
        config()->set('media-library.disk_name', 'nowhere');

        $this->artisan('storage:ensure-bucket')->assertSuccessful();
    }
}
