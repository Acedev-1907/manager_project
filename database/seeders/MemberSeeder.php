<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\User;
use App\Models\Project;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 2 user mẫu
        $user1 = User::firstOrCreate([
            'email' => 'test1@example.com',
        ], [
            'name' => 'Test User 1',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user2 = User::firstOrCreate([
            'email' => 'test2@example.com',
        ], [
            'name' => 'Test User 2',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);

        // user1 add user2 vào danh bạ
        Member::firstOrCreate([
            'user_id' => $user1->id,
            'member_id' => $user2->id,
        ]);
        // user2 add user1 vào danh bạ (nếu muốn)
        Member::firstOrCreate([
            'user_id' => $user2->id,
            'member_id' => $user1->id,
        ]);
    }
}
