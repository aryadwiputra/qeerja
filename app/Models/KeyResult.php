<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'title',
        'status',
        'current_value',
        'target_value',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'decimal:2',
            'target_value' => 'decimal:2',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function getProgressAttribute(): float
    {
        return $this->target_value > 0
            ? round(($this->current_value / $this->target_value) * 100, 1)
            : 0;
    }
}
