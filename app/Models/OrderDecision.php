<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDecision extends Model
{
    public $timestamps = false;

    protected $fillable = ['sales_order_id', 'decided_by', 'decision', 'note', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
