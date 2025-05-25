<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Game;
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
    public function __construct()
    {
        if (Game::query()->count() === 0) {
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
        // Get Teams
        $teams = Team::query()->pluck('id')->toArray();
        if (count($teams) % 2 !== 0) {
            $teams[] = null;
        }
        $n = count($teams);
        $rounds = ($n - 1) * 2;
        $half = $n - 1;
        $order = $teams;

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $n / 2; $i++) {
                $home = $order[$i];
                $away = $order[$n - 1 - $i];

                if (is_null($home) || is_null($away)) {
                    continue;
                }

                if ($round >= $half) {
                    [$home, $away] = [$away, $home];
                }

                Game::query()->create([
                    'week' => $round + 1,
                    'home_team_id' => $home,
                    'away_team_id' => $away,
                ]);
            }

            $slice = array_slice($order, 1, -1);
            array_unshift($slice, array_pop($order));
            $order = array_merge([$order[0]], $slice);
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
        $games = Game::query()
            ->where(
                'week',
                Game::query()
                    ->whereNull('home_score')
                    ->min('week')
            )
            ->get();

        $games->each(function ($m) {
            $scores = $this->simulateGame($m);
            $m->update($scores);
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
        $games = Game::query()->get();

        $games->each(function ($m) {
            $scores = $this->simulateGame($m);
            $m->update($scores);
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
        $game = Game::query()->findOrFail($id);
        $game->update(['home_score' => $home, 'away_score' => $away]);
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
        $out = collect();
        Team::all()->each(function ($team) use (&$out) {
            $played = Game::query()->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                    ->whereNotNull('home_score');
            })
                ->orWhere(function ($q) use ($team) {
                    $q->where('away_team_id', $team->id)
                        ->whereNotNull('away_score');
                })->get();

            $stats = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'played' => $played->count(),
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'for' => 0,
                'against' => 0,
                'gd' => 0,
                'points' => 0,
            ];

            foreach ($played as $m) {
                if ($m->home_team_id === $team->id) {
                    $f = $m->home_score;
                    $ag = $m->away_score;
                } else {
                    $f = $m->away_score;
                    $ag = $m->home_score;
                }
                $stats['for'] += $f;
                $stats['against'] += $ag;

                if ($f > $ag) {
                    $stats['won']++;
                    $stats['points'] += 3;
                } elseif ($f === $ag) {
                    $stats['drawn']++;
                    $stats['points'] += 1;
                } else {
                    $stats['lost']++;
                }
            }

            $stats['gd'] = $stats['for'] - $stats['against'];
            $out->push((object)$stats);
        });

        return $out
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
        $current = Game::query()->whereNotNull('home_score')->max('week') ?? 0;
        if ($current < $minWeek) {
            return collect();
        }

        $teams = Team::all();
        $total = $teams->sum('strength');

        return $teams->map(fn($t) => (object)[
            'team_id' => $t->id,
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
        return Game::query()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->get();
    }
}
