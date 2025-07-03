<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Xóa hoặc comment đoạn tạo user với email 'test@example.com' để tránh trùng lặp
        // User::create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'password' => bcrypt('password'),
        //     'isValidEmail' => 1,
        // ]);

        $this->call(\Database\Seeders\MemberSeeder::class);
    }
}
