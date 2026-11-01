<?php

namespace Database\\Seeders;

use App\\Models\\Hostel;
use Illuminate\\Database\\Seeder;

class HostelSeeder extends Seeder
{
    public function run(): void
    {
        $hostels = [
            ['name' => 'Ahilya Hostel', 'code' => 'AHILYA'],
            ['name' => 'Ambika Hostel', 'code' => 'AMBIKA'],
            ['name' => 'Savitri Hostel', 'code' => 'SAVITRI'],
            ['name' => 'Eklavya Hostel', 'code' => 'EKLAVYA'],
            ['name' => 'Vivekanand Hostel', 'code' => 'VIVEKANAND'],
        ];

        foreach ($hostels as $hostel) {
            Hostel::firstOrCreate(
                ['code' => $hostel['code']],
                ['name' => $hostel['name'], 'is_active' => true]
            );
        }
    }
}
