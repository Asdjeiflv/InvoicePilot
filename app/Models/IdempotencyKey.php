<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
