<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'uom', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    public function availableQty(): float
    {
        return $this->stock?->available() ?? 0.0;
    }
}
