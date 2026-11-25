<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(OpenPlaySession::class, 'open_play_session_players')
            ->withPivot(['queue_position', 'games_played', 'wins', 'losses', 'fixed_partner_id', 'checked_in_at', 'checked_out_at'])
            ->withTimestamps();
    }
}
