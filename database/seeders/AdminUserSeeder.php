<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email_hash' => hash('sha256', mb_strtolower($email))],
            [
                'name' => env('ADMIN_NAME', 'Şikayetçiyim Kıbrıs Admin'),
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');
    }
}
