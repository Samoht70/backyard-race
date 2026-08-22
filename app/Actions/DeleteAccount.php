<?php

namespace App\Actions;

use App\Models\User;
use App\Support\SparedAccount;
use Illuminate\Support\Facades\DB;

final class DeleteAccount
{
    /**
     * Deleting the row without the model leaves `model_has_roles` pointing at a
     * gone account: `HasRoles` detaches on the `deleting` event, not in the
     * database. Skipping the sessions leaves the runner's cookie signed in.
     */
    public function __invoke(User $account): bool
    {
        if (SparedAccount::spares($account)) {
            return false;
        }

        return DB::transaction(function () use ($account): bool {
            $account->delete();

            DB::table('sessions')->where('user_id', $account->getKey())->delete();

            return true;
        });
    }
}
