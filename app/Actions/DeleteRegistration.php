<?php

namespace App\Actions;

use App\Models\Participant;
use Illuminate\Support\Facades\DB;

final class DeleteRegistration
{
    public function __construct(private DeleteAccount $deleteAccount) {}

    public function __invoke(Participant $registration): void
    {
        DB::transaction(function () use ($registration): void {
            $account = $registration->user;

            $registration->delete();

            ($this->deleteAccount)($account);
        });
    }
}
