<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Support\OrganiserAddress;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role as RoleModel;

class PurgeRegistrationsCommand extends Command
{
    protected $signature = 'race:purge-registrations {--force}';

    protected $description = 'Delete every registration and every runner account, leaving the event and the manager accounts alone';

    public function handle(): int
    {
        $event = Event::query()->first();

        if ($event !== null && $event->lifecycle()->isRacing()) {
            $this->error("The event is `{$event->status->value}`: a registration is a runner on the course, not a test row.");

            return self::FAILURE;
        }

        $registrations = Participant::query()->count();
        $accounts = $this->runnerAccounts()->count();

        if ($registrations === 0 && $accounts === 0) {
            $this->info('Nothing to purge: no registration and no runner account.');

            return self::SUCCESS;
        }

        return $this->purgeOnceConfirmed($registrations, $accounts);
    }

    private function purgeOnceConfirmed(int $registrations, int $accounts): int
    {
        $this->announce($registrations, $accounts);

        if (! $this->confirmed()) {
            $this->error('Nothing was purged.');

            return self::FAILURE;
        }

        $sessions = $this->purge();

        $this->info("Purged {$registrations} registration(s), {$accounts} runner account(s) and {$sessions} session(s).");

        return self::SUCCESS;
    }

    private function announce(int $registrations, int $accounts): void
    {
        $this->warn("About to delete {$registrations} registration(s) and {$accounts} runner account(s).");
        $this->line('The event, its briefing, its documents and every spared account stay.');

        $queued = Queue::size();

        if ($queued > 0) {
            $this->warn("{$queued} job(s) are still queued: one addressed to a purged account fails when it wakes up.");
        }

        $this->warnAboutTheDoor($accounts);
    }

    private function warnAboutTheDoor(int $accounts): void
    {
        if (OrganiserAddress::configured() === null) {
            $this->warn('`RACE_ORGANISER_EMAIL` points at no usable address: the `manager` role alone spares an account.');
        }

        if ($accounts === User::query()->count()) {
            $this->warn('Not one account is spared, by role or by address: this purge leaves no way in. `race:manager-account` opens the door again.');
        }
    }

    private function confirmed(): bool
    {
        if ($this->option('force') === true) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('No terminal to confirm on: pass --force to purge without being asked.');

            return false;
        }

        return $this->confirm('Delete them?');
    }

    private function purge(): int
    {
        return DB::transaction(function (): int {
            $accounts = $this->runnerAccounts()->get();

            Participant::query()->get()->each->delete();
            $accounts->each->delete();

            return DB::table('sessions')->whereIn('user_id', $accounts->modelKeys())->delete();
        });
    }

    /**
     * @return Builder<User>
     */
    private function runnerAccounts(): Builder
    {
        $accounts = User::query()->whereDoesntHave('roles', $this->managerRole(...));
        $organiser = OrganiserAddress::configured();

        return $organiser === null ? $accounts : $accounts->where('email', '!=', $organiser);
    }

    /**
     * @param  Builder<RoleModel>  $roles
     */
    private function managerRole(Builder $roles): void
    {
        $roles->where('name', Role::Manager->value);
    }
}
