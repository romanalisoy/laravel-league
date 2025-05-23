<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public function homeMatches() {
        return $this->hasMany(, 'home_team_id');
    }

    public function awayMatches() {
        return $this->hasMany(Game::class, 'away_team_id');
    }
}
