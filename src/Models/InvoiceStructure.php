<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MetaFramework\Casts\PriceInteger;

class InvoiceStructure extends Model
{
    protected $table = 'mfw_accounts_invoices_structure';

    public $timestamps = false;

    protected $fillable
        = [
            'invoice_id',
            'content',
            'quantity',
            'amount',
            'vat',
            'vat_id',
        ];

    protected $casts = [
        'amount' => PriceInteger::class,
        'vat' => PriceInteger::class,
    ];

    public function vat(): BelongsTo
    {
        return $this->belongsTo(Vat::class, 'vat_id');
    }
}
