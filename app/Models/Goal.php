<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'status',
        'target_date',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class);
    }

    public function epics(): BelongsToMany
    {
        return $this->belongsToMany(Epic::class, 'epic_goals');
    }

    public function getProgressAttribute(): float
    {
        $keyResults = $this->keyResults;

        if ($keyResults->isEmpty()) {
            return 0;
        }

        $total = $keyResults->sum('target_value');
        $current = $keyResults->sum('current_value');

        return $total > 0 ? round(($current / $total) * 100, 1) : 0;
    }
}
