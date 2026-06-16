<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'name',
        'filters',
        'sort_field',
        'sort_direction',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
