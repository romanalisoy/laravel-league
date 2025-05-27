<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $strength
 * @property Game[] $homeGames
 * @property Game[] $awayGames
 */
class Team extends Model
{
    const TABLE = 'teams';

    protected $fillable = ['name', 'strength'];

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function calculateStats(): array
    {
        $played = $this->homeGames->merge($this->awayGames);
        $stats = [
            'team_id'  => $this->id,
            'team_name'=> $this->name,
            'played'   => $played->count(),
            'won'      => 0,
            'drawn'    => 0,
            'lost'     => 0,
            'for'      => 0,
            'against'  => 0,
            'gd'       => 0,
            'points'   => 0,
        ];

        foreach ($played as $game) {
            if ($game->home_team_id === $this->id) {
                $f  = $game->home_score;
                $ag = $game->away_score;
            } else {
                $f  = $game->away_score;
                $ag = $game->home_score;
            }
            $stats['for']     += $f;
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
        return $stats;
    }

}
