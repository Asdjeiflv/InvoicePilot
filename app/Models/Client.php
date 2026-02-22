<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $company_name
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $payment_terms_days
 * @property int|null $closing_day
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Quotation> $quotations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 */
class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'payment_terms_days',
        'closing_day',
        'notes',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'closing_day' => 'integer',
    ];

    /**
     * Get quotations for the client
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Get invoices for the client
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
