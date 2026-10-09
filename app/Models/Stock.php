<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    public const CREATED_AT = null;

    protected $fillable = ['product_id', 'qty_on_hand', 'qty_reserved'];

    protected $casts = [
        'qty_on_hand' => 'float',
        'qty_reserved' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Stok tersedia = qty_on_hand - qty_reserved (ASUMSI-6)
    public function available(): float
    {
        return $this->qty_on_hand - $this->qty_reserved;
    }
}
