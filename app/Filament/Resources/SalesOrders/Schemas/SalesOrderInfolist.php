<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use App\Filament\Resources\SalesOrders\Tables\SalesOrdersTable;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pesanan')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('order_no')->label('No. Pesanan'),
                        TextEntry::make('customer.name')->label('Customer'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state) => SalesOrdersTable::statusColor($state)),
                        TextEntry::make('creator.name')->label('Dibuat oleh'),
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i'),
                        TextEntry::make('submitted_at')->label('Di-submit')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Keputusan Supervisor')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record->decision !== null)
                    ->schema([
                        TextEntry::make('decision.decision')->label('Keputusan')->badge()
                            ->color(fn (string $state) => $state === 'approve' ? 'success' : 'danger'),
                        TextEntry::make('decision.decider.name')->label('Oleh'),
                        TextEntry::make('decision.decided_at')->label('Waktu')->dateTime('d/m/Y H:i'),
                        TextEntry::make('decision.note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
