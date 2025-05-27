<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Game;
use App\Repositories\IGameRepository;
use App\Repositories\ITeamRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class LeagueService
 *
 * Provides services for managing league operations, including generating fixtures,
 * simulating games, updating game results, calculating standings, and making predictions.
 */
class LeagueService
{

    /**
     * LeagueService constructor.
     *
     * Initializes the service and generates fixtures if no games exist in the database.
     */
    public function __construct(
        private readonly ITeamRepository $teamRepo,
        private readonly IGameRepository $gameRepo
    )
    {
        if ($this->gameRepo->count() === 0) {
            $this->generateFixtures();
        }
    }

    /**
     * Generate fixtures for the league.
     *
     * Creates a round-robin schedule for all teams in the league. If the number of teams
     * is odd, a dummy team is added to ensure even pairing.
     *
     * @return void
     */
    public function generateFixtures(): void
    {
        $teamIds = $this->teamRepo->pluckIds();

        if (count($teamIds) % 2 !== 0) {
            $teamIds[] = null;
        }

        $n = count($teamIds);
        $rounds = ($n - 1) * 2;
        $order = $teamIds;
        $fixtures = [];

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $n / 2; $i++) {
                $home = $order[$i];
                $away = $order[$n - 1 - $i];

                if (is_null($home) || is_null($away)) {
                    continue;
                }

                if ($round >= ($n - 1)) {
                    [$home, $away] = [$away, $home];
                }

                $fixtures[] = [
                    'week' => $round + 1,
                    'home_team_id' => $home,
                    'away_team_id' => $away,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $slice = array_slice($order, 1, -1);
            array_unshift($slice, array_pop($order));
            $order = array_merge([$order[0]], $slice);
        }

        if (!empty($fixtures)) {
            $this->gameRepo->insertFixtures($fixtures);
        }
    }

    /**
     * Simulate and play the next week's games.
     *
     * Updates the scores for all games in the next week and returns the updated games.
     *
     * @return Collection A collection of updated games for the next week.
     */
    public function playNextWeek(): Collection
    {
        $nextWeek = $this->gameRepo->minUnplayedWeek();
        $games = $this->gameRepo->getByWeekWithTeams($nextWeek);

        $games->each(function ($game) {
            $scores = $this->simulateGame($game);
            $this->gameRepo->updateScores($game, $scores);
            $game->setRawAttributes(array_merge($game->getAttributes(), $scores));
        });

        return $games;
    }

    /**
     * Simulate and play all remaining weeks' games.
     *
     * Updates the scores for all games that have not yet been played and returns the updated games.
     *
     * @return Collection A collection of updated games for all remaining weeks.
     */
    public function playAllWeeks(): Collection
    {
        $games = $this->gameRepo->getAllWithTeams();

        $games->each(function ($game) {
            $scores = $this->simulateGame($game);
            $this->gameRepo->updateScores($game, $scores);
            $game->setRawAttributes(array_merge($game->getAttributes(), $scores));
        });

        return $games;
    }

    /**
     * Simulate a single game and generate scores.
     *
     * Calculates the scores for the home and away teams based on their strength.
     *
     * @param Game $game The game to simulate.
     * @return array An array containing the home and away scores.
     */
    protected function simulateGame(Game $game): array
    {
        return [
            'home_score' => floor(rand(0, 5) * ($game->homeTeam->strength / 100)),
            'away_score' => floor(rand(0, 5) * ($game->awayTeam->strength / 100)),
        ];
    }

    /**
     * Update the result of a specific game.
     *
     * Updates the scores for a game identified by its ID.
     *
     * @param int $id The ID of the game to update.
     * @param int $home The home team's score.
     * @param int $away The away team's score.
     * @return Builder|EloquentCollection|Model|Builder[] The updated game instance.
     */
    public function updateGameResult(int $id, int $home, int $away): Builder|array|EloquentCollection|Model
    {
        $game = $this->gameRepo->find($id);
        $this->gameRepo->updateScores($game, [
            'home_score' => $home,
            'away_score' => $away,
        ]);
        $game->setRawAttributes(array_merge($game->getAttributes(), [
            'home_score' => $home,
            'away_score' => $away,
        ]));

        return $game;
    }

    /**
     * Get the current league standings.
     *
     * Calculates and returns the standings for all teams based on their performance.
     *
     * @return Collection A collection of team standings.
     */
    public function standings(): Collection
    {
        $teams = $this->teamRepo->allWithGames();
        return $teams->map(function (Team $team) {
            $stats = $team->calculateStats();
            return (object)$stats;
        })
            ->sortByDesc('points')
            ->sortByDesc('gd')
            ->values();
    }

    /**
     * Get predictions for the league.
     *
     * Calculates the probability of each team winning based on their strength.
     * Predictions are only available after a minimum number of weeks have been played.
     *
     * @param int $minWeek The minimum number of weeks required for predictions.
     * @return Collection A collection of team predictions with probabilities.
     */
    public function predictions(int $minWeek = 4): Collection
    {
        $currentWeek = $this->gameRepo->maxPlayedWeek() ?? 0;
        if ($currentWeek < $minWeek) {
            return collect();
        }

        $teams = $this->teamRepo->all();
        $total = $teams->sum('strength');

        return $teams->map(fn($t) => (object)[
            'team_id'     => $t->id,
            'probability' => round($t->strength / $total * 100, 2),
        ]);
    }

    /**
     * Get the fixtures for the league.
     *
     * Retrieves all games in the league, including their teams and scores.
     *
     * @return Collection A collection of game instances with team and score information.
     */
    public function fixtures(): Collection
    {
        return $this->gameRepo->getAllWithTeams();
    }
}
