<?php

namespace Tests\Feature;

use App\Exceptions\OrderException;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\SalesOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesOrderService $svc;
    private User $sales;
    private User $sales2;
    private User $supervisor;
    private User $warehouse;
    private Product $gkr; // stok 10

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->svc = app(SalesOrderService::class);
        $this->sales = User::where('email', 'sales@gula.test')->first();
        $this->sales2 = User::where('email', 'sales2@gula.test')->first();
        $this->supervisor = User::where('email', 'supervisor@gula.test')->first();
        $this->warehouse = User::where('email', 'warehouse@gula.test')->first();
        $this->gkr = Product::where('sku', 'GKR-50')->first();
    }

    private function draft(): SalesOrder
    {
        return $this->svc->create($this->sales, 1);
    }

    private function expectErr(string $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail("Harusnya {$code}");
        } catch (OrderException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public function test_buat_draft_dengan_nomor_otomatis(): void
    {
        $order = $this->draft();

        $this->assertSame(SalesOrder::DRAFT, $order->status);
        $this->assertMatchesRegularExpression('/^SO-\d{8}-0001$/', $order->order_no);
        $this->assertSame('created', AuditLog::first()->action);
    }

    public function test_tambah_item_mereservasi_stok(): void
    {
        $this->svc->addItem($this->draft(), $this->sales, $this->gkr->id, 4);

        $this->assertEquals(6, $this->gkr->fresh()->availableQty());
    }

    public function test_qty_melebihi_stok_ditolak_dan_stok_tidak_berubah(): void
    {
        $order = $this->draft();

        $this->expectErr('ERR-05', fn () => $this->svc->addItem($order, $this->sales, $this->gkr->id, 11));
        $this->assertEquals(10, $this->gkr->fresh()->availableQty());
        $this->assertSame(0, $order->items()->count());
    }

    public function test_qty_nol_dan_product_ganda_ditolak(): void
    {
        $order = $this->draft();
        $this->expectErr('ERR-04', fn () => $this->svc->addItem($order, $this->sales, $this->gkr->id, 0));

        $this->svc->addItem($order, $this->sales, $this->gkr->id, 1);
        $this->expectErr('ERR-06', fn () => $this->svc->addItem($order, $this->sales, $this->gkr->id, 1));
    }

    public function test_ubah_qty_menyesuaikan_reservasi(): void
    {
        $item = $this->svc->addItem($this->draft(), $this->sales, $this->gkr->id, 4);

        $this->svc->updateItem($item, $this->sales, 10);
        $this->assertEquals(0, $this->gkr->fresh()->availableQty());

        $this->svc->updateItem($item, $this->sales, 2);
        $this->assertEquals(8, $this->gkr->fresh()->availableQty());

        $this->expectErr('ERR-05', fn () => $this->svc->updateItem($item, $this->sales, 11));
    }

    public function test_dua_pesanan_tidak_bisa_melebihi_stok(): void
    {
        $this->svc->addItem($this->draft(), $this->sales, $this->gkr->id, 7);

        $other = $this->svc->create($this->sales2, 1);
        $this->expectErr('ERR-05', fn () => $this->svc->addItem($other, $this->sales2, $this->gkr->id, 4));
        $this->assertEquals(3, $this->gkr->fresh()->availableQty());
    }

    public function test_submit_kosong_ditolak_dan_submitted_terkunci(): void
    {
        $order = $this->draft();
        $this->expectErr('ERR-09', fn () => $this->svc->submit($order, $this->sales));

        $item = $this->svc->addItem($order, $this->sales, $this->gkr->id, 2);
        $this->svc->submit($order, $this->sales);

        $this->expectErr('ERR-08', fn () => $this->svc->updateItem($item, $this->sales, 3));
    }

    public function test_approve_mempertahankan_reservasi(): void
    {
        $order = $this->draft();
        $this->svc->addItem($order, $this->sales, $this->gkr->id, 4);
        $this->svc->submit($order, $this->sales);

        $order = $this->svc->approve($order, $this->supervisor);

        $this->assertSame(SalesOrder::APPROVED, $order->status);
        $this->assertEquals(6, $this->gkr->fresh()->availableQty());
    }

    public function test_reject_wajib_catatan_dan_melepas_reservasi(): void
    {
        $order = $this->draft();
        $this->svc->addItem($order, $this->sales, $this->gkr->id, 4);
        $this->svc->submit($order, $this->sales);

        $this->expectErr('ERR-10', fn () => $this->svc->reject($order, $this->supervisor, ' '));

        $order = $this->svc->reject($order, $this->supervisor, 'Harga belum sesuai');
        $this->assertSame(SalesOrder::REJECTED, $order->status);
        $this->assertEquals(10, $this->gkr->fresh()->availableQty());
    }

    public function test_transisi_tidak_valid_dan_role_salah_ditolak(): void
    {
        $order = $this->draft();
        $this->svc->addItem($order, $this->sales, $this->gkr->id, 1);

        $this->expectErr('ERR-07', fn () => $this->svc->approve($order, $this->supervisor));
        $this->svc->submit($order, $this->sales);
        $this->expectErr('ERR-02', fn () => $this->svc->approve($order, $this->sales));
        $this->expectErr('ERR-02', fn () => $this->svc->addItem($order, $this->sales2, $this->gkr->id, 1));

        $this->svc->approve($order, $this->supervisor);
        $this->expectErr('ERR-07', fn () => $this->svc->reject($order, $this->supervisor, 'x'));
    }

    public function test_hapus_draft_melepas_reservasi_dan_audit_tetap_ada(): void
    {
        $order = $this->draft();
        $this->svc->addItem($order, $this->sales, $this->gkr->id, 5);

        $this->svc->deleteDraft($order, $this->sales);

        $this->assertEquals(10, $this->gkr->fresh()->availableQty());
        $this->assertSame('draft_deleted', AuditLog::latest('id')->first()->action);
    }

    public function test_visibilitas_per_role(): void
    {
        $mine = $this->draft();
        $this->svc->addItem($mine, $this->sales, $this->gkr->id, 1);
        $this->svc->submit($mine, $this->sales);
        $this->svc->approve($mine, $this->supervisor);
        $this->svc->create($this->sales2, 1);

        $this->assertSame(1, SalesOrder::visibleTo($this->sales)->count());
        $this->assertSame(2, SalesOrder::visibleTo($this->supervisor)->count());
        $this->assertSame([$mine->id], SalesOrder::visibleTo($this->warehouse)->pluck('id')->all());
    }

    public function test_audit_log_tidak_bisa_diubah(): void
    {
        $this->draft();

        $this->expectException(\LogicException::class);
        AuditLog::first()->update(['action' => 'x']);
    }
}
