<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ
        DB::table('members')->truncate();
        DB::table('users')->truncate();

        // Tạo 8 user mẫu
        $user1 = User::create([
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'phone' => '0123456781',
            'avatar' => 'https://i.pravatar.cc/150?img=1',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'phone' => '0123456782',
            'avatar' => 'https://i.pravatar.cc/150?img=2',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user3 = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'phone' => '0123456783',
            'avatar' => 'https://i.pravatar.cc/150?img=3',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user4 = User::create([
            'name' => 'Test User 4',
            'email' => 'test4@example.com',
            'phone' => '0123456784',
            'avatar' => 'https://i.pravatar.cc/150?img=4',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user5 = User::create([
            'name' => 'Test User 5',
            'email' => 'test5@example.com',
            'phone' => '0123456785',
            'avatar' => 'https://i.pravatar.cc/150?img=5',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user6 = User::create([
            'name' => 'Test User 6',
            'email' => 'test6@example.com',
            'phone' => '0123456786',
            'avatar' => 'https://i.pravatar.cc/150?img=6',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user7 = User::create([
            'name' => 'Test User 7',
            'email' => 'test7@example.com',
            'phone' => '0123456787',
            'avatar' => 'https://i.pravatar.cc/150?img=7',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);
        $user8 = User::create([
            'name' => 'Test User 8',
            'email' => 'test8@example.com',
            'phone' => '0123456788',
            'avatar' => 'https://i.pravatar.cc/150?img=8',
            'password' => bcrypt('password'),
            'isValidEmail' => 1,
        ]);

        $users = [$user1, $user2, $user3, $user4, $user5, $user6, $user7, $user8];
        // Mỗi user add 4 user còn lại vào danh bạ
        foreach ($users as $u) {
            foreach ($users as $other) {
                if ($u->id !== $other->id) {
                    Member::create([
                        'user_id' => $u->id,
                        'member_id' => $other->id,
                    ]);
                }
            }
        }
    }
}
