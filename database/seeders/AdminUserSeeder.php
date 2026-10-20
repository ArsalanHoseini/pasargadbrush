<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Local development only — creates a default admin login.
     * Change this password immediately on any shared/staging environment.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@pasargadbrush.local'],
            [
                'name' => 'مدیر سیستم',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );
    }
}
