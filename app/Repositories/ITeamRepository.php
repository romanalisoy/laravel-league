<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface ITeamRepository
{
    public function pluckIds(): array;
    public function all(): Collection;
    public function allWithGames(): Collection;
}
