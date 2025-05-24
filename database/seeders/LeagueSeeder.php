<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('league.teams') as $teams) {
            Team::query()->create($teams);
        }
    }
}
