<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $table = 'tournaments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'court_count' => 'integer',
            'schedule_mode' => 'string',
            'match_duration_minutes' => 'integer',
        ];
    }
}
