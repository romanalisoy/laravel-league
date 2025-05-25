<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Services\LeagueService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class LeagueServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeagueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service =  $this->app->make(LeagueService::class);
    }

    public function test_generate_fixtures_creates_double_round_robin()
    {
        $this->assertEquals(12, Game::query()->count());
        $weeks = Game::query()->distinct()->pluck('week')->sort()->values()->toArray();
        $this->assertCount(6, $weeks);
        $this->assertEquals(range(1,6), $weeks);
    }

    public function test_play_next_week_populates_scores()
    {
        $this->assertTrue(Game::query()->whereNull('home_score')->exists());

        $games = $this->service->playNextWeek();
        $this->assertCount(2, $games);

        foreach ($games as $m) {
            $this->assertNotNull($m->home_score);
            $this->assertNotNull($m->away_score);
        }
    }

    public function test_play_all_weeks_finishes_all_games()
    {
        $this->service->playAllWeeks();
        $this->assertFalse(Game::query()->whereNull('home_score')->exists());
    }

    public function test_update_gam_result_overwrites_scores()
    {
        $gam = Game::query()->first();
        $original = $gam->home_score;
        $updated = $this->service->updateGameResult($gam->id, 3, 1);

        $this->assertEquals(3, $updated->home_score);
        $this->assertEquals(1, $updated->away_score);
        $this->assertNotEquals($original, $updated->home_score);
    }

    public function test_standings_calculates_points_goal_difference()
    {
        $m = Game::query()->first();
        $m->update(['home_score'=>2,'away_score'=>0]);

        $standings = $this->service->standings();
        $first = $standings->first();

        $this->assertEquals($m->homeTeam->id, $first->team_id);
        $this->assertEquals(1, $first->played);
        $this->assertEquals(1, $first->won);
        $this->assertEquals(0, $first->drawn);
        $this->assertEquals(0, $first->lost);
        $this->assertEquals(2, $first->{'for'});
        $this->assertEquals(0, $first->against);
        $this->assertEquals(2, $first->gd);
        $this->assertEquals(3, $first->points);
    }

    public function test_predictions_only_after_fourth_week()
    {
        for ($i = 1; $i <= 4; $i++) {
            Game::query()->where('week', $i)->get()->each(fn($m)=> $m->update(['home_score'=>1,'away_score'=>1]));
        }
        $preds = $this->service->predictions();
        $this->assertCount(4, $preds);
        $sum = $preds->sum('probability');
        $this->assertEqualsWithDelta(100, $sum, 0.1);
    }

    public function test_predictions_before_fourth_week_returns_empty()
    {
        $empty = $this->service->predictions();
        $this->assertTrue($empty->isEmpty());
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }
}
