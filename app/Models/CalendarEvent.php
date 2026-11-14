<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property string $color
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $location
 * @property bool $is_all_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project|null $project
 */
#[Fillable([
    'user_id',
    'project_id',
    'title',
    'description',
    'type',
    'color',
    'starts_at',
    'ends_at',
    'start_time',
    'end_time',
    'location',
    'is_all_day',
])]
class CalendarEvent extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_all_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'calendar_event_id');
    }
}
