<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $quotation_no
 * @property int $client_id
 * @property Carbon $issue_date
 * @property Carbon|null $valid_until
 * @property float $subtotal
 * @property float $tax_total
 * @property float $total
 * @property string $status
 * @property Carbon|null $sent_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Client $client
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuotationItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AuditLog> $auditLogs
 */
class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quotation_no',
        'client_id',
        'issue_date',
        'valid_until',
        'subtotal',
        'tax_total',
        'total',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $appends = ['expiry_date'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft']);
    }

    /**
     * Accessor for expiry_date (maps to valid_until)
     */
    public function getExpiryDateAttribute()
    {
        return $this->valid_until;
    }
}
