<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupAdminAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-admin-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the admin account with id=1';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if deployment environment is "self-hosted"
        if (config("scrybble.deployment_environment") !== "self-hosted") {
            $this->error('This command can only be run in a self-hosted environment.');
            return 1;
        }

        // Check if user with id=1 already exists
        if (User::find(1)) {
            $this->error('An admin account with ID 1 already exists.');
            return 1;
        }

        // Prompt for username
        $username = $this->ask('Enter admin username');

        // Check if username already exists
        if (User::where('name', $username)->exists()) {
            $this->error("A user with the username '{$username}' already exists.");
            return 1;
        }

        // Prompt for password
        $password = $this->secret('Enter admin password');
        $passwordConfirmation = $this->secret('Confirm admin password');

        // Validate password
        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');
            return 1;
        }

        // Create the admin user
        $user = new User();
        $user->id = 1;
        $user->name = $username;
        $user->password = Hash::make($password);
        $user->email = "Doesn't matter";
        $user->save();

        $this->info('Admin account created successfully!');
        return 0;
    }
}
