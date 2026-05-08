<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class PeterpanSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['ic_number' => '870318065795'],
            [
                'name' => 'Peterpan',
                'email' => 'peterpan@jtmk.local',
                'password' => 'password',
                'phone' => '0127482616',
                'account_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => null,
                'is_super_admin' => true,
            ]
        );
    }
}
