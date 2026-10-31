<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class HostelRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Warden', 'Hostel Supervisor'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }
}
