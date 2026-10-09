<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\SalesOrderService;
use Illuminate\Database\Seeder;

// 5 transaksi demo, satu per status, dibuat lewat service agar reservasi stok dan audit trail terisi.
// Jalankan: php artisan db:seed --class=DemoOrderSeeder
class DemoOrderSeeder extends Seeder
{
    public function run(SalesOrderService $svc): void
    {
        $sales = User::where('email', 'sales@gula.test')->firstOrFail();
        $sales2 = User::where('email', 'sales2@gula.test')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@gula.test')->firstOrFail();

        $customer = fn (string $code) => Customer::where('code', $code)->value('id');
        $product = fn (string $sku) => Product::where('sku', $sku)->value('id');

        // 1. APPROVED — siap disiapkan Warehouse
        $o = $svc->create($sales, $customer('CUST-001'), 'Pengiriman awal bulan');
        $svc->addItem($o, $sales, $product('GKP-50'), 100);
        $svc->addItem($o, $sales, $product('GKP-01'), 500);
        $svc->submit($o, $sales);
        $svc->approve($o, $supervisor, 'OK, sesuai kuota distributor');

        // 2. APPROVED — tanpa catatan approve
        $o = $svc->create($sales2, $customer('CUST-002'));
        $svc->addItem($o, $sales2, $product('GKP-50'), 50);
        $svc->submit($o, $sales2);
        $svc->approve($o, $supervisor);

        // 3. REJECTED — stok dikembalikan
        $o = $svc->create($sales, $customer('CUST-003'), 'Permintaan mendadak');
        $svc->addItem($o, $sales, $product('GKP-50'), 200);
        $svc->submit($o, $sales);
        $svc->reject($o, $supervisor, 'Melebihi kuota bulanan customer, ajukan ulang bulan depan');

        // 4. SUBMITTED — menunggu keputusan Supervisor
        $o = $svc->create($sales2, $customer('CUST-001'), 'Tambahan stok toko cabang');
        $svc->addItem($o, $sales2, $product('GKP-01'), 300);
        $svc->addItem($o, $sales2, $product('GKP-50'), 25);
        $svc->submit($o, $sales2);

        // 5. DRAFT — masih disusun Sales (jumlah sempat diubah)
        $o = $svc->create($sales, $customer('CUST-002'), 'Draft, menunggu konfirmasi customer');
        $item = $svc->addItem($o, $sales, $product('GKP-01'), 150);
        $svc->updateItem($item, $sales, 120);
    }
}
