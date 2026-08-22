<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Support\AccessCode;
use App\Support\EmailAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role as RoleModel;

class ManagerAccountCommand extends Command
{
    protected $signature = 'race:manager-account {email} {first-name?} {last-name?} {--regenerate}';

    protected $description = 'Create the organiser account and show its access code once, or issue a new code for an existing account';

    public function handle(): int
    {
        $email = EmailAddress::normalise($this->text('email'));

        if (Validator::make(['email' => $email], ['email' => ['required', 'string', 'email', 'max:255']])->fails()) {
            $this->error("`{$email}` is not an email address: pass the organiser's address as the first argument.");

            return self::FAILURE;
        }

        if (! RoleModel::query()->where('name', Role::Manager->value)->exists()) {
            $this->error('The `manager` role does not exist yet: run `php artisan db:seed --class=RolesAndPermissionsSeeder` first.');

            return self::FAILURE;
        }

        $account = User::query()->where('email', $email)->first();

        return $account === null ? $this->createAccount($email) : $this->reissueCode($account);
    }

    private function createAccount(string $email): int
    {
        $firstName = $this->text('first-name');
        $lastName = $this->text('last-name');

        if ($firstName === '' || $lastName === '') {
            $this->error("No account carries `{$email}` yet: pass a first name and a last name to create one.");

            return self::FAILURE;
        }

        $code = AccessCode::generate();

        $account = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $code,
        ]);

        $account->assignRole(Role::Manager);

        return $this->show($account, $code);
    }

    private function reissueCode(User $account): int
    {
        if ($this->option('regenerate') !== true) {
            $this->error("`{$account->email}` already has an account: add --regenerate to issue a new access code.");

            return self::FAILURE;
        }

        $code = AccessCode::generate();

        $account->update(['password' => $code]);

        return $this->show($account, $code);
    }

    private function show(User $account, string $code): int
    {
        $this->info("Access code for {$account->email}: {$code}");
        $this->comment('It is stored hashed and shown this once only.');

        return self::SUCCESS;
    }

    private function text(string $name): string
    {
        $typed = $this->argument($name);

        return is_string($typed) ? $typed : '';
    }
}
