<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'name' => 'Jacob Atam',
            'email' => 'jacob.atam@gmail.com',
            'password' => '1122334455',
        ];

        if (Schema::hasColumn('users', 'is_admin')) {
            $data['is_admin'] = true;
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'jacob.atam@gmail.com'],
            $data
        );

        if (Schema::hasTable('user_permissions') && ! $user->users_permission()->exists()) {
            $user->users_permission()->create([
                'permission_id' => 1,
            ]);
        }
    }
}
