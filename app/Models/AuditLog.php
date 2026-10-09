<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// Append-only (BR-11): update/hapus ditolak.
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['sales_order_id', 'user_id', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values', 'note'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log tidak dapat diubah atau dihapus.'));
        static::deleting(fn () => throw new LogicException('Audit log tidak dapat diubah atau dihapus.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
