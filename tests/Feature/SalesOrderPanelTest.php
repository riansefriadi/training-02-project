<?php

namespace Tests\Feature;

use App\Filament\Resources\SalesOrders\Pages\CreateSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\ListSalesOrders;
use App\Filament\Resources\SalesOrders\Pages\ViewSalesOrder;
use App\Filament\Resources\SalesOrders\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\SalesOrders\RelationManagers\ItemsRelationManager;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesOrderPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->first();
    }

    public function test_alur_lengkap_lewat_panel(): void
    {
        $this->actingAs($this->user('sales@gula.test'));

        $this->get('/admin/sales-orders')->assertOk();

        Livewire::test(CreateSalesOrder::class)
            ->fillForm(['customer_id' => 1, 'note' => 'Kirim minggu depan'])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = SalesOrder::first();
        $gkr = Product::where('sku', 'GKR-50')->first();

        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewSalesOrder::class])
            ->callAction(TestAction::make('create')->table(), ['product_id' => $gkr->id, 'qty' => 11])
            ->assertNotified();
        $this->assertSame(0, $order->items()->count());

        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewSalesOrder::class])
            ->callAction(TestAction::make('create')->table(), ['product_id' => $gkr->id, 'qty' => 4]);
        $this->assertSame(1, $order->items()->count());

        Livewire::test(ViewSalesOrder::class, ['record' => $order->id])
            ->assertActionVisible('submit')
            ->assertActionHidden('approve')
            ->callAction('submit');
        $this->assertSame(SalesOrder::SUBMITTED, $order->fresh()->status);

        $this->actingAs($this->user('supervisor@gula.test'));
        Livewire::test(ViewSalesOrder::class, ['record' => $order->id])
            ->assertActionHidden('submit')
            ->callAction('reject', ['note' => 'Stok dialihkan']);
        $this->assertSame(SalesOrder::REJECTED, $order->fresh()->status);
        $this->assertEquals(10, $gkr->fresh()->availableQty());

        Livewire::test(AuditLogsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewSalesOrder::class])
            ->assertOk()
            ->assertSee('rejected');
    }

    public function test_warehouse_hanya_melihat_approved(): void
    {
        $this->actingAs($this->user('warehouse@gula.test'));

        $order = SalesOrder::create([
            'order_no' => 'SO-X', 'customer_id' => 1,
            'created_by' => $this->user('sales@gula.test')->id, 'status' => SalesOrder::DRAFT,
        ]);

        Livewire::test(ListSalesOrders::class)->assertCanNotSeeTableRecords([$order]);
        $this->get("/admin/sales-orders/{$order->id}")->assertNotFound();
        $this->get('/admin/sales-orders/create')->assertForbidden();
    }
}
