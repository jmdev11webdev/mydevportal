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
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $color
 * @property array<int, string>|null $tech_stack
 * @property string|null $repository_url
 * @property string|null $live_url
 * @property Carbon|null $started_at
 * @property Carbon|null $due_at
 * @property int|null $tasks_count
 * @property int|null $completed_tasks_count
 */
#[Fillable([
    'user_id',
    'name',
    'description',
    'status',
    'color',
    'tech_stack',
    'repository_url',
    'live_url',
    'started_at',
    'due_at',
])]
class Project extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'started_at' => 'date',
            'due_at' => 'date',
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
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<CalendarEvent, $this>
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
