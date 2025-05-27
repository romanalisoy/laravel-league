<?php
namespace App\Providers;

use App\Repositories\Eloquent\GameRepository;
use App\Repositories\Eloquent\TeamRepository;
use App\Repositories\IGameRepository;
use App\Repositories\ITeamRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ITeamRepository::class,
            TeamRepository::class
        );

        $this->app->bind(
            IGameRepository::class,
            GameRepository::class
        );
    }
}
