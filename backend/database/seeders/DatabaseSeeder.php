<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@energeek.id',
            'password' => Hash::make('tes123'), 
            'role' => 'admin',
        ]);

        LeaveType::create([
            'name' => 'Annual Leave',
            'default_quota' => 12,
        ]);

        LeaveType::create([
            'name' => 'Sick Leave',
            'default_quota' => 6,
        ]);
    }
}