<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EliminationMatch extends Model
{
    protected $guarded = [];

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Teams::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Teams::class, 'team2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Teams::class, 'winner_id');
    }

    protected function casts(): array
    {
        return [
            'is_finalized' => 'boolean',
            'team1_score' => 'integer',
            'team2_score' => 'integer',
        ];
    }
}
