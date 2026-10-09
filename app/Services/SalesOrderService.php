<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\OrderDecision;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Stock;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Business rule BR-01..BR-14 (docs/design/02-rules.md).
class SalesOrderService
{
    public function create(User $user, int $customerId, ?string $note = null): SalesOrder
    {
        $this->assertRole($user->isSalesAdmin());

        $customer = Customer::where('id', $customerId)->where('is_active', true)->first();
        if (! $customer) {
            throw new OrderException('ERR-11', 'Customer atau product tidak tersedia.');
        }

        return DB::transaction(function () use ($user, $customer, $note) {
            $order = SalesOrder::create([
                'order_no' => $this->nextOrderNo(),
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'status' => SalesOrder::DRAFT,
                'note' => $note,
            ]);

            $this->audit($order, $user, 'created', 'sales_order', $order->id, null, [
                'order_no' => $order->order_no,
                'customer_id' => $customer->id,
                'note' => $note,
            ]);

            return $order;
        });
    }

    public function addItem(SalesOrder $order, User $user, int $productId, float $qty): SalesOrderItem
    {
        return DB::transaction(function () use ($order, $user, $productId, $qty) {
            $order = $this->lockEditable($order, $user);
            $this->assertQty($qty);

            $product = Product::where('id', $productId)->where('is_active', true)->first();
            if (! $product) {
                throw new OrderException('ERR-11', 'Customer atau product tidak tersedia.');
            }
            if ($order->items()->where('product_id', $productId)->exists()) {
                throw new OrderException('ERR-06', 'Product sudah ada di pesanan ini. Ubah jumlah pada item yang ada.');
            }

            $this->reserve($product, $qty);

            $item = $order->items()->create(['product_id' => $productId, 'qty' => $qty]);
            $item->reservation()->create([
                'product_id' => $productId,
                'qty' => $qty,
                'status' => StockReservation::ACTIVE,
                'reserved_at' => now(),
            ]);

            $this->audit($order, $user, 'item_added', 'sales_order_item', $item->id, null, [
                'product' => $product->sku,
                'qty' => $qty,
            ]);

            return $item;
        });
    }

    public function updateItem(SalesOrderItem $item, User $user, float $qty): SalesOrderItem
    {
        return DB::transaction(function () use ($item, $user, $qty) {
            $order = $this->lockEditable($item->salesOrder, $user);
            $this->assertQty($qty);

            $item = SalesOrderItem::lockForUpdate()->findOrFail($item->id);
            $oldQty = $item->qty;
            $delta = $qty - $oldQty;

            if ($delta > 0) {
                $this->reserve($item->product, $delta);
            } elseif ($delta < 0) {
                $this->release($item->product_id, -$delta);
            }

            $item->update(['qty' => $qty]);
            $item->reservation()->update(['qty' => $qty]);

            $this->audit($order, $user, 'item_changed', 'sales_order_item', $item->id,
                ['product' => $item->product->sku, 'qty' => $oldQty],
                ['product' => $item->product->sku, 'qty' => $qty],
            );

            return $item;
        });
    }

    public function removeItem(SalesOrderItem $item, User $user): void
    {
        DB::transaction(function () use ($item, $user) {
            $order = $this->lockEditable($item->salesOrder, $user);

            $this->releaseItem($item);
            $this->audit($order, $user, 'item_removed', 'sales_order_item', $item->id,
                ['product' => $item->product->sku, 'qty' => $item->qty], null,
            );
            $item->delete();
        });
    }

    public function deleteDraft(SalesOrder $order, User $user): void
    {
        DB::transaction(function () use ($order, $user) {
            $order = $this->lockEditable($order, $user);

            foreach ($order->items as $item) {
                $this->releaseItem($item);
            }
            $this->audit($order, $user, 'draft_deleted', 'sales_order', $order->id,
                ['order_no' => $order->order_no, 'status' => $order->status], null,
            );
            $order->delete();
        });
    }

    public function submit(SalesOrder $order, User $user): SalesOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            $this->assertRole($user->isSalesAdmin() && $order->isOwnedBy($user));
            $this->assertStatus($order, SalesOrder::SUBMITTED);

            if (! $order->items()->exists()) {
                throw new OrderException('ERR-09', 'Pesanan harus memiliki minimal satu item.');
            }

            $order->update(['status' => SalesOrder::SUBMITTED, 'submitted_at' => now()]);
            $this->audit($order, $user, 'submitted', 'sales_order', $order->id,
                ['status' => SalesOrder::DRAFT], ['status' => SalesOrder::SUBMITTED],
            );

            return $order;
        });
    }

    public function approve(SalesOrder $order, User $user, ?string $note = null): SalesOrder
    {
        return $this->decide($order, $user, 'approve', $note);
    }

    public function reject(SalesOrder $order, User $user, ?string $note): SalesOrder
    {
        return $this->decide($order, $user, 'reject', $note);
    }

    private function decide(SalesOrder $order, User $user, string $decision, ?string $note): SalesOrder
    {
        $this->assertRole($user->isSupervisor());

        $note = $note !== null ? trim($note) : null;
        if (($decision === 'reject' && blank($note)) || mb_strlen((string) $note) > 1000) {
            throw new OrderException('ERR-10', 'Catatan wajib diisi (maksimal 1000 karakter).');
        }

        return DB::transaction(function () use ($order, $user, $decision, $note) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            $newStatus = $decision === 'approve' ? SalesOrder::APPROVED : SalesOrder::REJECTED;
            $this->assertStatus($order, $newStatus);

            OrderDecision::create([
                'sales_order_id' => $order->id,
                'decided_by' => $user->id,
                'decision' => $decision,
                'note' => $note ?: null,
                'decided_at' => now(),
            ]);

            // BR-09: reject melepas reservasi, approve mempertahankan.
            if ($decision === 'reject') {
                foreach ($order->items as $item) {
                    $this->releaseItem($item);
                }
            }

            $order->update(['status' => $newStatus, 'decided_at' => now()]);
            $this->audit($order, $user, $newStatus, 'sales_order', $order->id,
                ['status' => SalesOrder::SUBMITTED], ['status' => $newStatus], $note ?: null,
            );

            return $order;
        });
    }

    // BR-03: cek dan tambah reservasi dalam satu UPDATE bersyarat (atomik).
    private function reserve(Product $product, float $qty): void
    {
        $affected = Stock::where('product_id', $product->id)
            ->whereRaw('qty_on_hand - qty_reserved >= 0 + ?', [$qty])
            ->increment('qty_reserved', $qty);

        if ($affected === 0) {
            $available = (float) Stock::where('product_id', $product->id)
                ->selectRaw('qty_on_hand - qty_reserved as available')
                ->value('available');

            throw new OrderException('ERR-05', sprintf(
                'Stok tidak cukup. Tersedia: %s %s, diminta: %s.',
                $this->fmt($available), $product->uom, $this->fmt($qty),
            ));
        }
    }

    private function release(int $productId, float $qty): void
    {
        Stock::where('product_id', $productId)->decrement('qty_reserved', $qty);
    }

    private function releaseItem(SalesOrderItem $item): void
    {
        $reservation = $item->reservation;
        if ($reservation && $reservation->status === StockReservation::ACTIVE) {
            $this->release($item->product_id, $reservation->qty);
            $reservation->update(['status' => StockReservation::RELEASED, 'released_at' => now()]);
        }
    }

    // BR-05: hanya draft milik Sales Admin pembuat.
    private function lockEditable(SalesOrder $order, User $user): SalesOrder
    {
        $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
        $this->assertRole($user->isSalesAdmin() && $order->isOwnedBy($user));

        if (! $order->isDraft()) {
            throw new OrderException('ERR-08', "Pesanan berstatus {$order->status} dan tidak dapat diubah.");
        }

        return $order;
    }

    // BR-07, BR-10: transisi yang valid saja.
    private function assertStatus(SalesOrder $order, string $to): void
    {
        $allowed = [
            SalesOrder::SUBMITTED => SalesOrder::DRAFT,
            SalesOrder::APPROVED => SalesOrder::SUBMITTED,
            SalesOrder::REJECTED => SalesOrder::SUBMITTED,
        ];

        if ($order->status !== $allowed[$to]) {
            throw new OrderException('ERR-07', "Aksi tidak dapat dilakukan pada pesanan berstatus {$order->status}.");
        }
    }

    private function assertRole(bool $allowed): void
    {
        if (! $allowed) {
            throw new OrderException('ERR-02', 'Anda tidak memiliki hak akses untuk aksi ini.');
        }
    }

    private function assertQty(float $qty): void
    {
        if ($qty <= 0) {
            throw new OrderException('ERR-04', 'Jumlah harus lebih dari 0.');
        }
    }

    // BR-14: SO-yyyymmdd-nnnn
    private function nextOrderNo(): string
    {
        $prefix = 'SO-'.now()->format('Ymd').'-';
        $last = SalesOrder::where('order_no', 'like', $prefix.'%')->lockForUpdate()->max('order_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function audit(SalesOrder $order, User $user, string $action, string $entityType, ?int $entityId, ?array $old, ?array $new, ?string $note = null): void
    {
        AuditLog::create([
            'sales_order_id' => $order->id,
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'note' => $note,
        ]);
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
    }
}
