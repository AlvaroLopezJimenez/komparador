<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Álvaro López',
            'email' => 'srtocoque@gmail.com',
            'password' => Hash::make('..Hercules950'),
            'email_verified_at' => now(),
        ]);
    }
}
