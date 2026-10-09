<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    public const ACTIVE = 'active';
    public const RELEASED = 'released';

    public $timestamps = false;

    protected $fillable = ['sales_order_item_id', 'product_id', 'qty', 'status', 'reserved_at', 'released_at'];

    protected $casts = [
        'qty' => 'float',
        'reserved_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }
}
