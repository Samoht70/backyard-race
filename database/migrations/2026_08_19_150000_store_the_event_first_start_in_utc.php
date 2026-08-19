<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-reads the column under the UTC convention rounds already use.
     *
     * The schema does not change: a DATETIME carries no offset, so the stored
     * wall-clock is simply reinterpreted. Left alone, the origin of every lap
     * hour would keep the convention its own derivatives were fixed against.
     */
    public function up(): void
    {
        $this->shift(config()->string('app.timezone'), 'UTC');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->shift('UTC', config()->string('app.timezone'));
    }

    private function shift(string $from, string $to): void
    {
        foreach (DB::table('events')->whereNotNull('first_start_at')->get(['id', 'first_start_at']) as $event) {
            DB::table('events')->where('id', $event->id)->update([
                'first_start_at' => CarbonImmutable::parse((string) $event->first_start_at, $from)
                    ->setTimezone($to)
                    ->format('Y-m-d H:i:s'),
            ]);
        }
    }
};
