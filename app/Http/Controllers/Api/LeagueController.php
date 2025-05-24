<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditMatchRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\PredictionResource;
use App\Http\Resources\StandingResource;
use App\Models\Game;
use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class LeagueController
 *
 * Handles API endpoints related to league operations such as standings,
 * current week, next week games, and predictions.
 */
class LeagueController extends Controller
{
    /**
     * LeagueController constructor.
     *
     * @param LeagueService $leagueService The service handling league-related logic.
     */
    public function __construct(protected LeagueService $leagueService)
    {
    }

    /**
     * Get the current league standings.
     *
     * @return AnonymousResourceCollection A collection of standings resources.
     */
    public function standings(): AnonymousResourceCollection
    {
        return StandingResource::collection($this->leagueService->standings());
    }

    /**
     * Get the current week of the league.
     *
     * @return JsonResponse A JSON response containing the current week number.
     */
    public function currentWeek(): JsonResponse
    {
        return response()->json([
            'current_week' => Game::query()->whereNotNull('home_score')->max('week') ?? 0
        ]);
    }

    /**
     * Play the next week's games and return the results.
     *
     * @return AnonymousResourceCollection A collection of game resources for the next week.
     */
    public function nextWeek(): AnonymousResourceCollection
    {
        return GameResource::collection($this->leagueService->playNextWeek());
    }

    /**
     * Play all remaining weeks' games and return the results.
     *
     * @return AnonymousResourceCollection A collection of game resources for all weeks.
     */
    public function playAll(): AnonymousResourceCollection
    {
        return GameResource::collection($this->leagueService->playAllWeeks());
    }

    /**
     * Edit the result of a specific game.
     *
     * @param EditMatchRequest $req The request containing the updated game scores.
     * @param int $id The ID of the game to be updated.
     * @return GameResource The updated game resource.
     */
    public function editGame(EditMatchRequest $req, int $id): GameResource
    {
        return new GameResource($this->leagueService->updateGameResult($id, $req->home_score, $req->away_score));
    }

    /**
     * Get predictions for the league.
     *
     * @return AnonymousResourceCollection A collection of prediction resources.
     */
    public function predictions(): AnonymousResourceCollection
    {
        return PredictionResource::collection($this->leagueService->predictions());
    }

    /**
     * Get the fixtures for the league.
     *
     * @return AnonymousResourceCollection A collection of game resources for the fixtures.
     */
    public function fixtures(): AnonymousResourceCollection
    {
        return GameResource::collection($this->leagueService->fixtures());
    }

}
