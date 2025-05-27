<?php

namespace App\Repositories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

interface IGameRepository
{
    public function count(): int;
    public function insertFixtures(array $fixtures): void;
    public function minUnplayedWeek(): int;
    public function getByWeekWithTeams(int $week): Collection;
    public function getAllWithTeams(): Collection;
    public function find(int $id): Builder|array|EloquentCollection|Model|Game;
    public function updateScores(Game $game, array $scores): void;
    public function maxPlayedWeek(): ?int;
}
