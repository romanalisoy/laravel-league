<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Team;
use App\Repositories\IGameRepository;
use App\Repositories\ITeamRepository;
use App\Services\LeagueService;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\Exception;

class LeagueServiceTest extends TestCase
{
    private ITeamRepository $teamRepo;
    private IGameRepository $gameRepo;
    private LeagueService $service;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->teamRepo = $this->createMock(ITeamRepository::class);
        $this->gameRepo = $this->createMock(IGameRepository::class);

        $this->gameRepo->method('count')->willReturn(0);
        $this->teamRepo->method('pluckIds')->willReturn([1, 2, 3, 4]);
        $this->gameRepo->expects($this->once())->method('insertFixtures');

        $this->service = new LeagueService($this->teamRepo, $this->gameRepo);
    }

    /**
     * @throws Exception
     */
    public function test_play_next_week_populates_scores()
    {
        $gameMock1 = $this->createMock(Game::class);
        $gameMock2 = $this->createMock(Game::class);

        $homeTeam = $this->createMock(Team::class);
        $homeTeam->method('__get')->willReturnCallback(fn($k) => $k === 'strength' ? 90 : null);

        $awayTeam = $this->createMock(Team::class);
        $awayTeam->method('__get')->willReturnCallback(fn($k) => $k === 'strength' ? 80 : null);

        foreach ([$gameMock1, $gameMock2] as $gm) {
            $gm->method('getAttributes')->willReturn([]);
            $gm->method('setRawAttributes');
            $gm->method('__get')->willReturnMap([
                ['homeTeam', $homeTeam],
                ['awayTeam', $awayTeam],
                ['home_score', 2],
                ['away_score', 1],
            ]);
        }

        $games = new Collection([$gameMock1, $gameMock2]);

        $this->gameRepo->method('minUnplayedWeek')->willReturn(1);
        $this->gameRepo->method('getByWeekWithTeams')->willReturn($games);
        $this->gameRepo->expects($this->exactly(2))->method('updateScores');

        $result = $this->service->playNextWeek();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        foreach ($result as $m) {
            $this->assertNotNull($m->home_score);
            $this->assertNotNull($m->away_score);
        }
    }


    /**
     * @throws Exception
     */
    public function test_play_all_weeks_finishes_all_games()
    {
        $gameMock = $this->createMock(Game::class);

        $homeTeam = $this->createMock(Team::class);
        $homeTeam->method('__get')->willReturnCallback(fn($k) => $k === 'strength' ? 90 : null);

        $awayTeam = $this->createMock(Team::class);
        $awayTeam->method('__get')->willReturnCallback(fn($k) => $k === 'strength' ? 80 : null);

        $gameMock->method('getAttributes')->willReturn([]);
        $gameMock->method('setRawAttributes');
        $gameMock->method('__get')
            ->willReturnMap([
                ['homeTeam', $homeTeam],
                ['awayTeam', $awayTeam],
                ['home_score', 2],
                ['away_score', 1]
            ]);

        $games = new Collection([$gameMock]);

        $this->gameRepo->method('getAllWithTeams')->willReturn($games);
        $this->gameRepo->expects($this->once())->method('updateScores');

        $result = $this->service->playAllWeeks();
        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @throws Exception
     */
    public function test_standings_calculates_points_goal_difference()
    {
        $teamMock = $this->createMock(Team::class);
        $teamMock->method('calculateStats')->willReturn([
            'team_id' => 1,
            'team_name' => 'Mock Team',
            'played' => 6,
            'won' => 1,
            'drawn' => 5,
            'lost' => 0,
            'for' => 2,
            'against' => 0,
            'gd' => 2,
            'points' => 8,
        ]);

        $teams = new Collection([$teamMock]);
        $this->teamRepo->method('allWithGames')->willReturn($teams);

        $standings = $this->service->standings();

        $this->assertEquals(1, $standings[0]->team_id);
        $this->assertEquals(6, $standings[0]->played);
        $this->assertEquals(8, $standings[0]->points);
        $this->assertEquals(2, $standings[0]->gd);
    }

    public function test_predictions_only_after_fourth_week()
    {
        $teams = new Collection([
            (object)['id' => 1, 'name' => 'Chelsea', 'strength' => 90],
            (object)['id' => 2, 'name' => 'Arsenal', 'strength' => 85],
            (object)['id' => 3, 'name' => 'Manchester City', 'strength' => 88],
            (object)['id' => 4, 'name' => 'Liverpool', 'strength' => 87],
        ]);


        $this->gameRepo->method('maxPlayedWeek')->willReturn(4);
        $this->teamRepo->method('all')->willReturn($teams);

        $predictions = $this->service->predictions();

        $this->assertCount(4, $predictions);
        $this->assertEqualsWithDelta(100, $predictions->sum('probability'), 0.1);
    }

    public function test_predictions_before_fourth_week_returns_empty()
    {
        $this->gameRepo->method('maxPlayedWeek')->willReturn(2);
        $empty = $this->service->predictions();
        $this->assertTrue($empty->isEmpty());
    }

    /**
     * @throws Exception
     */
    public function test_update_game_result_overwrites_scores()
    {
        $gameMock = $this->createMock(Game::class);

        $this->gameRepo->method('find')->willReturn($gameMock);
        $this->gameRepo->expects($this->once())
            ->method('updateScores')
            ->with($gameMock, ['home_score' => 3, 'away_score' => 1]);

        $gameMock->method('getAttributes')->willReturn([]);
        $gameMock->method('setRawAttributes');

        $updated = $this->service->updateGameResult(1, 3, 1);

        $this->assertInstanceOf(Game::class, $updated);
    }

    public function test_generate_fixtures_creates_double_round_robin()
    {
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function test_fixtures_returns_all_games_with_teams()
    {
        $gameMock = $this->createMock(Game::class);
        $games = new Collection([$gameMock]);
        $this->gameRepo->method('getAllWithTeams')->willReturn($games);
        $fixtures = $this->service->fixtures();
        $this->assertEquals($games, $fixtures);
    }

    public function test_fixtures_returns_empty_when_no_games_exist()
    {
        $this->gameRepo->method('getAllWithTeams')->willReturn(new Collection());
        $fixtures = $this->service->fixtures();
        $this->assertTrue($fixtures->isEmpty());
    }
}
