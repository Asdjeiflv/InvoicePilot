<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;  // Only has created_at

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'before_json',
        'after_json',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an action
     */
    public static function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $before = null,
        ?array $after = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_json' => $before,
            'after_json' => $after,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
