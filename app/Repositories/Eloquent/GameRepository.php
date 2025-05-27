<?php

namespace App\Repositories\Eloquent;

use App\Models\Game;
use App\Repositories\IGameRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GameRepository implements IGameRepository
{

    public function count(): int
    {
        return Game::query()->count();
    }

    public function insertFixtures(array $fixtures): void
    {
        Game::query()->insert($fixtures);
    }

    public function minUnplayedWeek(): int
    {
        return Game::query()->whereNull('home_score')->min('week');
    }

    public function getByWeekWithTeams(int $week): Collection
    {
        return Game::with(['homeTeam', 'awayTeam'])
            ->where('week', $week)
            ->get();
    }

    public function getAllWithTeams(): Collection
    {
        return Game::with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->get();
    }

    public function find(int $id): Builder|array|EloquentCollection|Model|Game
    {
        return Game::query()->findOrFail($id);
    }

    public function updateScores(Game $game, array $scores): void
    {
        $game->update($scores);
    }

    public function maxPlayedWeek(): ?int
    {
        return Game::query()->whereNotNull('home_score')->max('week');
    }

}
