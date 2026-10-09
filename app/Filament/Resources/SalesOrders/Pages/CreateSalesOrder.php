<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Filament\Concerns\HandlesOrderErrors;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Services\SalesOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesOrder extends CreateRecord
{
    use HandlesOrderErrors;

    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return static::runOrderAction(fn () => app(SalesOrderService::class)
            ->create(auth()->user(), (int) $data['customer_id'], $data['note'] ?? null));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
