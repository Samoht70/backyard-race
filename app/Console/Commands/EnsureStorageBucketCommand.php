<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

class EnsureStorageBucketCommand extends Command
{
    protected $signature = 'storage:ensure-bucket';

    protected $description = 'Create the object storage bucket the media library writes to, when it is missing';

    public function handle(): int
    {
        /** @var array<string, mixed> $disk */
        $disk = config('filesystems.disks.s3');

        $bucket = (string) $disk['bucket'];
        $client = $this->client($disk);

        if ($client->doesBucketExistV2($bucket, false)) {
            $this->comment("Bucket `{$bucket}` is already there.");

            return self::SUCCESS;
        }

        $client->createBucket(['Bucket' => $bucket]);

        $this->comment("Bucket `{$bucket}` created.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $disk
     */
    private function client(array $disk): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $disk['region'],
            'endpoint' => $disk['endpoint'],
            'use_path_style_endpoint' => (bool) $disk['use_path_style_endpoint'],
            'credentials' => [
                'key' => $disk['key'],
                'secret' => $disk['secret'],
            ],
        ]);
    }
}
