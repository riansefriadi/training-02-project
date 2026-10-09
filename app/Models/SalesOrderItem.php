<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrderItem extends Model
{
    protected $fillable = ['sales_order_id', 'product_id', 'qty'];

    protected $casts = ['qty' => 'float'];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(StockReservation::class);
    }
}
