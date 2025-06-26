<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('🔐 Starting Role and User Seeder');

        // Create roles
        Log::info('🔐 Creating roles');
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        Log::info('✅ Roles created successfully', [
            'admin_role_id' => $adminRole->id,
            'user_role_id' => $userRole->id
        ]);

        // Create admin user
        Log::info('👤 Creating admin user');
        $adminUser = User::create([
            'name' => 'Administrator',
            'email' => 'admin@chatbot.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $adminUser->assignRole($adminRole);

        Log::info('✅ Admin user created successfully', [
            'user_id' => $adminUser->id,
            'email' => $adminUser->email,
            'role' => 'admin'
        ]);

        // Create regular user
        Log::info('👤 Creating regular user');
        $regularUser = User::create([
            'name' => 'John Doe',
            'email' => 'user@chatbot.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $regularUser->assignRole($userRole);

        Log::info('✅ Regular user created successfully', [
            'user_id' => $regularUser->id,
            'email' => $regularUser->email,
            'role' => 'user'
        ]);

        // Create some additional random users
        Log::info('👥 Creating additional random users');
        $randomUsers = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Carol Davis',
                'email' => 'carol@example.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($randomUsers as $userData) {
            $user = User::create(array_merge($userData, [
                'email_verified_at' => now(),
            ]));
            $user->assignRole($userRole);

            Log::info('✅ Random user created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => 'user'
            ]);
        }

        Log::info('🎉 Role and User Seeder completed successfully', [
            'total_users_created' => User::count(),
            'total_roles_created' => Role::count()
        ]);

        // Display summary
        $this->command->info('🔐 Roles and Users created successfully!');
        $this->command->info('👤 Admin user: admin@chatbot.com (password: password123)');
        $this->command->info('👤 Regular user: user@chatbot.com (password: password123)');
        $this->command->info('👥 Additional users created with role: user');
    }
}
