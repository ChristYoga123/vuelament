<?php

namespace App\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUserCommand extends Command
{
    protected $signature = 'vuelament:user
                            {--name= : Nama user}
                            {--email= : Email user}
                            {--password= : Password user}
                            {--role=super_admin : Role yang diberikan (default: super_admin)}';

    protected $description = 'Buat user untuk akses panel Vuelament';

    public function handle(): int
    {
        $name     = $this->option('name') ?: $this->ask('Name');
        $email    = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $role     = $this->option('role');

        $panel    = app('vuelament.panel');
        $userModel = $panel->getUserModel()
            ?: config('auth.providers.users.model', \App\Models\User::class);

        // Cek apakah email sudah ada
        $user = $userModel::where('email', $email)->first();

        if ($user) {
            $this->warn("User [{$email}] sudah ada.");

            if ($this->confirm('Assign role dan update password?', true)) {
                $user->update(['password' => Hash::make($password)]);
                $this->assignRole($user, $role);
                $this->info("✅ User [{$email}] di-update dan role [{$role}] di-assign.");
            }

            return self::SUCCESS;
        }

        // Buat user baru
        $user = $userModel::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->assignRole($user, $role);

        $this->info("✅ User [{$email}] berhasil dibuat dengan role [{$role}].");
        $this->newLine();
        $this->line("  Login: /" . $panel->getPath() . "/login");

        return self::SUCCESS;
    }

    protected function assignRole($user, string $role): void
    {
        if (!method_exists($user, 'assignRole')) {
            $this->warn('  spatie/permission belum terinstall, role tidak di-assign.');
            return;
        }

        // Pastikan role ada
        $permissionModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);
        $permissionModel::findOrCreate($role);

        $user->assignRole($role);
    }
}
