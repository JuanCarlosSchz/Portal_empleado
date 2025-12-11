<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'super@portalempleado.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('cesurfp'),
                'role' => 'admin',
                'dni' => null,
                'active' => true,
            ]
        );
    }
}
