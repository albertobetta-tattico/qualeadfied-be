<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed the admins table with a default super admin.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@qualeadfied.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => 'password', // Cast 'hashed' in model handles hashing
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );
    }
}
