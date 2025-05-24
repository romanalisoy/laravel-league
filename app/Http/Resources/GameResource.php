<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'week'       => $this->week,
            'home_team'  => ['id'=>$this->homeTeam->id,'name'=>$this->homeTeam->name],
            'away_team'  => ['id'=>$this->awayTeam->id,'name'=>$this->awayTeam->name],
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
        ];
    }
}
