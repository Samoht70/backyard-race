<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ParticipantSeeder extends Seeder
{
    private const CONFIRMED = 12;

    public function run(): void
    {
        $event = Event::query()->first();

        if ($event === null || Participant::query()->exists()) {
            return;
        }

        $runners = User::query()
            ->role(Role::Participant->value)
            ->whereDoesntHave('participant')
            ->get();

        $this->register($event, $runners->take(self::CONFIRMED), Participant::factory()->confirmed());
        $this->register($event, $runners->skip(self::CONFIRMED), Participant::factory());
    }

    /**
     * @param  Collection<int, User>  $runners
     */
    private function register(Event $event, Collection $runners, ParticipantFactory $factory): void
    {
        foreach ($runners as $runner) {
            $factory->create([
                'event_id' => $event->id,
                'user_id' => $runner->id,
            ]);
        }
    }
}
