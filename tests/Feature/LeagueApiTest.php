<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_get_standings_endpoint()
    {
        $resp = $this->getJson('/api/league/standings');
        $resp->assertStatus(200)
            ->assertJsonStructure([ ['team_id','team_name','played','won','drawn','lost','for','against','gd','points'] ]);
    }

    public function test_post_next_week_endpoint()
    {
        $resp = $this->postJson('/api/league/next-week');
        $resp->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([ ['id','week','home_team','away_team','home_score','away_score'] ]);
    }

    public function test_post_play_all_endpoint()
    {
        $resp = $this->postJson('/api/league/play-all');
        $resp->assertStatus(200);
        $resp->assertJsonCount(12);
        $this->assertFalse(Game::query()->whereNull('home_score')->exists());
    }

    public function test_put_edit_game_endpoint()
    {
        $this->postJson('/api/league/next-week');
        $game = Game::query()->first();

        $resp = $this->putJson("/api/league/game/$game->id", [
            'home_score' => 5,
            'away_score' => 2,
        ]);
        $resp->assertStatus(200)
            ->assertJsonPath('home_score', 5)
            ->assertJsonPath('away_score', 2);
    }

    public function test_get_predictions_endpoint()
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/api/league/next-week');
        }

        $resp = $this->getJson('/api/league/predictions');
        $resp->assertStatus(200)
            ->assertJsonCount(4)
            ->assertJsonStructure([ ['team_id','probability'] ]);
    }

    public function test_current_week_endpoint()
    {
        $this->postJson('/api/league/next-week');
        $resp = $this->getJson('/api/league/current-week');
        $resp->assertStatus(200)
            ->assertJson(['current_week' => 1]);
    }
}
