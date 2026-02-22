<?php

namespace App\Models;

use App\Traits\HasOptimisticLock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property Carbon $payment_date
 * @property float $amount
 * @property string $method
 * @property string|null $reference_no
 * @property string|null $note
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice $invoice
 */
class Payment extends Model
{
    use HasFactory, HasOptimisticLock;

    protected $fillable = [
        'invoice_id',
        'payment_date',
        'amount',
        'method',
        'reference_no',
        'note',
        'created_by',
        'version',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who created this payment.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who recorded this payment.
     * This is an alias for creator() for backwards compatibility with frontend.
     *
     * @return BelongsTo
     */
    public function recordedBy(): BelongsTo
    {
        return $this->creator();
    }
}
