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
 * @property string|null $company
 * @property string|null $contact
 * @property string $stage
 * @property string|null $source
 * @property int|null $value
 * @property Carbon|null $follow_up_date
 * @property string|null $notes
 * @property Carbon|null $won_at
 * @property Carbon|null $lost_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'name',
    'company',
    'contact',
    'stage',
    'source',
    'value',
    'follow_up_date',
    'notes',
    'won_at',
    'lost_at',
])]
class PipelineLead extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    public const STAGES = [
        'new',
        'contacted',
        'proposal',
        'won',
        'lost',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'follow_up_date' => 'date:Y-m-d',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
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
