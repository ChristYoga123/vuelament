<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::findOrCreate('super_admin');
        $adminUser = User::query()
                        ->firstOrCreate([
                            'name' => 'Admin',
                            'email' => 'admin@gmail.com'
                        ], [
                            'password' => bcrypt('password')
                        ]);

        $adminUser->assignRole($adminRole);
    }
}
