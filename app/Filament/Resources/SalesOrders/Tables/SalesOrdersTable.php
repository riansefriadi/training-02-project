<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use App\Models\SalesOrder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_no')->label('No. Pesanan')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('creator.name')->label('Sales Admin'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('items_count')->label('Item')->counts('items'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SalesOrder::STATUSES),
            ])
            ->emptyStateHeading('Belum ada pesanan')
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            SalesOrder::SUBMITTED => 'warning',
            SalesOrder::APPROVED => 'success',
            SalesOrder::REJECTED => 'danger',
            default => 'gray',
        };
    }
}
