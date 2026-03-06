<?php

namespace ChristYoga123\Vuelament\Commands;

use ChristYoga123\Vuelament\Vuelament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUserCommand extends Command
{
    protected $signature = 'vuelament:user
                            {--name= : Name user}
                            {--email= : Email user}
                            {--password= : Password user}
                            {--role=super_admin : Role yang diberikan (default: super_admin)}
                            {--panel= : Panel ID (default: dari config vuelament.default_panel)}';

    protected $description = 'Create user untuk akses panel Vuelament';

    public function handle(): int
    {
        /** @var Vuelament $registry */
        $registry = app('vuelament');

        // ── Resolve panel ────────────────────────────
        $panelId = $this->option('panel');

        if ($panelId) {
            // Validate panel ID exists
            if (!$registry->hasPanel($panelId)) {
                $this->error("Panel [{$panelId}] is not registered.");
                $this->line('  Available panels: ' . implode(', ', $registry->getPanelIds()));
                return self::FAILURE;
            }
            $registry->setCurrentPanel($panelId);
        }

        // Jika ada lebih dari 1 panel dan --panel tidak diberikan, tampilkan pilihan
        $panels = $registry->getPanels();
        if (!$panelId && count($panels) > 1) {
            $panelId = $this->choice(
                'Which panel should this user be created for?',
                $registry->getPanelIds(),
                $registry->getDefaultPanelId()
            );
            $registry->setCurrentPanel($panelId);
        }

        $panel = $registry->getCurrentPanel();

        // ── Gather user data ─────────────────────────
        $name     = $this->option('name') ?: $this->ask('Name');
        $email    = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $role     = $this->option('role');

        $userModel = $panel->getUserModel()
            ?: config('auth.providers.users.model', \App\Models\User::class);

        // Cek apakah email sudah ada
        $user = $userModel::where('email', $email)->first();

        if ($user) {
            $this->warn("User [{$email}] sudah ada.");

            if ($this->confirm('Assign role and update password?', true)) {
                $user->update(['password' => Hash::make($password)]);
                $this->assignRole($user, $role);
                $this->info("✅ User [{$email}] di-update and role [{$role}] di-assign.");
            }

            return self::SUCCESS;
        }

        // Create user baru
        $user = $userModel::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->assignRole($user, $role);

        $this->info("✅ User [{$email}] created successfully with role [{$role}].");
        $this->newLine();
        $this->line("  Panel: {$panel->getId()}");
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

