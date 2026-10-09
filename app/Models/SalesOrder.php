<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public const STATUSES = [
        self::DRAFT => 'Draft',
        self::SUBMITTED => 'Submitted',
        self::APPROVED => 'Approved',
        self::REJECTED => 'Rejected',
    ];

    protected $fillable = ['order_no', 'customer_id', 'created_by', 'status', 'note', 'submitted_at', 'decided_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function decision(): HasOne
    {
        return $this->hasOne(OrderDecision::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderBy('created_at')->orderBy('id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    // Visibilitas BR-13: Sales Admin miliknya, Supervisor semua, Warehouse hanya approved.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isSupervisor() => $query,
            $user->isSalesAdmin() => $query->where('created_by', $user->id),
            $user->isWarehouse() => $query->where('status', self::APPROVED),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
