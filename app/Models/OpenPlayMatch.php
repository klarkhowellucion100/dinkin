<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenPlayMatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(OpenPlaySession::class, 'open_play_session_id');
    }

    public function team1Player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'team1_player1_id');
    }

    public function team1Player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'team1_player2_id');
    }

    public function team2Player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'team2_player1_id');
    }

    public function team2Player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'team2_player2_id');
    }
}
