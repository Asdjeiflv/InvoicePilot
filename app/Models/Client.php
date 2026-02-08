<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
