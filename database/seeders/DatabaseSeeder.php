<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MasterDataSeeder::class);
        $this->call(AiMasterSeeder::class);
        $this->call(ContohVersiMatriksPaketSeeder::class);
        $this->call(DefaultMatrixRecommendationConfigSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Konsultan Demo',
            'email' => 'konsultan@example.com',
        ]);

        $this->call(ContohAssessmentSeeder::class);
    }
}
