<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int $week
 * @property int|null $home_score
 * @property int|null $away_score
 * @property Team $homeTeam
 * @property Team $awayTeam
 */
class Game extends Model
{
    const TABLE = 'games';

    protected $fillable = [
        'home_team_id','away_team_id','week','home_score','away_score'
    ];

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class,'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class,'away_team_id');
    }

    public function isPlayed(): bool
    {
        return !is_null($this->home_score) && !is_null($this->away_score);
    }
}
