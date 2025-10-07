<?php

namespace Database\Seeders;

use App\Models\Permission;
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
   // $this->call([
        //     PermissionSeeder::class,
        // ]);
     

        Permission::factory()->create([
            'name' => 'read',
        ]);
        Permission::factory()->create([
            'name' => 'write',
        ]);
        Permission::factory()->create([
            'name' => 'update',
        ]);
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
