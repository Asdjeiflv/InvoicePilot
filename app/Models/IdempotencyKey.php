<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $key
 * @property int $user_id
 * @property string $response_json
 * @property int $response_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class IdempotencyKey extends Model
{
    protected $fillable = [
        'key',
        'user_id',
        'response_json',
        'response_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the idempotency key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cleanup old idempotency keys (older than 24 hours).
     */
    public static function cleanup(): void
    {
        static::where('created_at', '<', now()->subHours(24))->delete();
    }
}
