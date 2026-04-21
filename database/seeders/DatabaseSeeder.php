<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create Test Admin User
        \App\Models\User::factory()->create([
            'name' => 'Demo User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        \App\Models\GcpProject::create([
            'project_id' => 'vps1-493508',
            'credentials_file' => 'vps1-493508-fc5f8d5bb876.json',
            'is_active' => true,
            'is_full' => false,
        ]);
    }
}
