<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenPlaySession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'open_play_session_players')
            ->withPivot(['queue_position', 'games_played', 'wins', 'losses', 'fixed_partner_id', 'checked_in_at', 'checked_out_at'])
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(OpenPlayMatch::class);
    }
}
