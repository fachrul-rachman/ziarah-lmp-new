<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'xxxx',
                'name' => 'xxxx',
                'password' => 'xxxx',
            ],
            [
                'email' => 'xxx',
                'name' => 'xxx',
                'password' => 'xxxx',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}