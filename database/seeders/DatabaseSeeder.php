<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

// Data awal master (FR-1). Password semua user demo: password
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            Role::SALES_ADMIN => 'Sales Admin',
            Role::SUPERVISOR => 'Supervisor',
            Role::WAREHOUSE => 'Warehouse',
        ])->map(fn ($name, $code) => Role::updateOrCreate(['code' => $code], ['name' => $name]));

        $users = [
            ['sales@gula.test', 'Sales Admin 1', Role::SALES_ADMIN],
            ['sales2@gula.test', 'Sales Admin 2', Role::SALES_ADMIN],
            ['supervisor@gula.test', 'Supervisor', Role::SUPERVISOR],
            ['warehouse@gula.test', 'Warehouse', Role::WAREHOUSE],
        ];
        foreach ($users as [$email, $name, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role_id' => $roles[$role]->id,
                'is_active' => true,
            ]);
        }

        $customers = [
            ['CUST-001', 'PT Distributor Gula Lampung', true],
            ['CUST-002', 'CV Sumber Manis Jaya', true],
            ['CUST-003', 'UD Toko Grosir Sejahtera', true],
            ['CUST-004', 'PT Pangan Nusantara (nonaktif)', false],
        ];
        foreach ($customers as [$code, $name, $active]) {
            Customer::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => $active]);
        }

        $products = [
            ['GKP-50', 'Gula Kristal Putih 50 kg', 'karung', 500],
            ['GKP-01', 'Gula Kristal Putih 1 kg', 'pak', 2000],
            ['GKR-50', 'Gula Kristal Rafinasi 50 kg', 'karung', 10],
            ['TTS-01', 'Tetes Tebu', 'ton', 0],
        ];
        foreach ($products as [$sku, $name, $uom, $qty]) {
            $product = Product::updateOrCreate(['sku' => $sku], ['name' => $name, 'uom' => $uom, 'is_active' => true]);
            $product->stock()->firstOrCreate([], ['qty_on_hand' => $qty, 'qty_reserved' => 0]);
        }
    }
}
