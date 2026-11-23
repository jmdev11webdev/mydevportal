<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $category
 * @property string $status
 * @property string|null $version
 * @property string|null $url
 * @property string|null $notes
 * @property bool $is_pinned
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'name',
    'category',
    'status',
    'version',
    'url',
    'notes',
    'is_pinned',
])]
class StackItem extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    public const CATEGORIES = [
        'frontend',
        'backend',
        'database',
        'devops',
        'tooling',
        'ai',
    ];

    public const STATUSES = [
        'core',
        'proficient',
        'learning',
        'evaluating',
        'retired',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
