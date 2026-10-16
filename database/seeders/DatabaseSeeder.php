<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@polkesban.ac.id'],
            [
                'name'              => 'Admin Poltekkes Bandung',
                'password'          => Hash::make('ChangeMe!2026'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            ProgramStudiSeeder::class,
            BeritaSeeder::class,
        ]);
    }
}
