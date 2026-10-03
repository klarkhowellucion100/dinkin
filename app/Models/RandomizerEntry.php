<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['randomizer_session_id', 'name', 'bracket_number', 'position'])]
class RandomizerEntry extends Model
{
    public function session(): BelongsTo
    {
        return $this->belongsTo(RandomizerSession::class, 'randomizer_session_id');
    }
}
