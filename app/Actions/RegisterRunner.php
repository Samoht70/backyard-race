<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\RegistrationConfirmed;
use App\Support\AccessCode;
use Illuminate\Support\Facades\DB;

final class RegisterRunner
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Event $event, string $email, array $attributes): string
    {
        $code = AccessCode::generate();

        $user = DB::transaction(function () use ($event, $email, $attributes, $code): User {
            $user = User::create([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'email' => $email,
                'password' => $code,
            ]);

            $user->assignRole(Role::Participant);

            $participant = new Participant($attributes);
            $participant->event()->associate($event);
            $participant->user()->associate($user);
            $participant->save();

            return $user;
        });

        $user->notify(new RegistrationConfirmed($code));

        return $code;
    }
}
