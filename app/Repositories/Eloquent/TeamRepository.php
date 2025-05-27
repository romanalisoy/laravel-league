<?php

namespace App\Repositories\Eloquent;

use App\Models\Team;
use App\Repositories\ITeamRepository;
use Illuminate\Support\Collection;

class TeamRepository implements ITeamRepository
{

    public function pluckIds(): array
    {
        return Team::query()->pluck('id')->toArray();
    }

    public function all(): Collection
    {
        return Team::all();
    }

    public function allWithGames(): Collection
    {
        return Team::with(['homeGames', 'awayGames'])->get();
    }
}
