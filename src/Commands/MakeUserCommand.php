<?php

namespace ChristYoga123\Vuelament\Commands;

use ChristYoga123\Vuelament\Vuelament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MakeUserCommand extends Command
{
    protected $signature = 'vuelament:user
                            {--name= : Name user}
                            {--email= : Email user}
                            {--password= : Password user (WARNING: akan tersimpan di shell history! Biarkan kosong agar ditanya secara interaktif)}
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
        $name  = $this->option('name')  ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $role  = $this->option('role');

        // [FIX] Warn jika password diberikan via CLI argument
        // karena akan tersimpan di shell history (bash_history, zsh_history, dll.)
        if ($this->option('password')) {
            $this->warn('  ⚠  WARNING: Passing --password via CLI argument is insecure.');
            $this->warn('     The password may be stored in your shell history (~/.bash_history, ~/.zsh_history).');
            $this->warn('     Consider removing it from history after this command.');

            Log::warning('Vuelament: vuelament:user was called with --password argument. Password may be stored in shell history.');
        }

        $password = $this->option('password') ?: $this->secret('Password');

        $userModel = $panel->getUserModel()
            ?: config('auth.providers.users.model', \App\Models\User::class);

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Email [{$email}] tidak valid.");
            return self::FAILURE;
        }

        // Cek apakah email sudah ada
        $user = $userModel::where('email', $email)->first();

        if ($user) {
            $this->warn("User [{$email}] sudah ada.");

            if ($this->confirm('Assign role and update password?', true)) {
                // [FIX] Sudah pakai Hash::make — konsisten dengan register()
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
