<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name=Admin : Admin name}
                            {--email=admin@needanestimate.com : Admin email}
                            {--password= : Admin password (prompted if not provided)}';

    protected $description = 'Create the default admin user for Filament panel';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password') ?: $this->secret('Enter admin password');

        if (!$password) {
            $this->error('Password is required.');
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($this->confirm("User {$email} already exists. Update to admin role?", true)) {
                $existing->update(['role' => 'admin']);
                $this->info("✅ User {$email} updated to admin role.");
                return self::SUCCESS;
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->info("✅ Admin user created successfully!");
        $this->table(['Field', 'Value'], [
            ['Name', $user->name],
            ['Email', $user->email],
            ['Role', 'admin'],
        ]);

        return self::SUCCESS;
    }
}
