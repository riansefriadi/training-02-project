<?php

namespace App\Filament\Resources\SalesOrders\RelationManagers;

use App\Filament\Concerns\HandlesOrderErrors;
use App\Models\Product;
use App\Models\SalesOrderItem;
use App\Services\SalesOrderService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    use HandlesOrderErrors;

    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Pesanan';

    public function isReadOnly(): bool
    {
        return false;
    }

    // BR-05: hanya draft milik Sales Admin pembuat.
    private function canEditItems(): bool
    {
        $user = auth()->user();
        $order = $this->getOwnerRecord();

        return $order->isDraft() && $user->isSalesAdmin() && $order->isOwnedBy($user);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('Product')
                ->options(fn () => Product::with('stock')->where('is_active', true)->orderBy('name')->get()
                    ->mapWithKeys(fn (Product $p) => [
                        $p->id => "{$p->name} — tersedia {$this->fmt($p->availableQty())} {$p->uom}",
                    ]))
                ->searchable()
                ->required()
                ->hiddenOn('edit'),
            TextInput::make('qty')
                ->label('Jumlah')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->helperText(fn (?SalesOrderItem $record) => $record
                    ? 'Stok tersedia (di luar pesanan ini): '.$this->fmt($record->product->availableQty()).' '.$record->product->uom
                    : null),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.sku')->label('SKU'),
                TextColumn::make('product.name')->label('Product'),
                TextColumn::make('qty')->label('Jumlah')->formatStateUsing(fn ($state, SalesOrderItem $record) => $this->fmt((float) $state).' '.$record->product->uom),
                TextColumn::make('reservation.status')->label('Reservasi')->badge()
                    ->color(fn (?string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->emptyStateHeading('Belum ada item')
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Item')
                    ->visible(fn () => $this->canEditItems())
                    ->using(fn (array $data) => static::runOrderAction(fn () => app(SalesOrderService::class)
                        ->addItem($this->getOwnerRecord(), auth()->user(), (int) $data['product_id'], (float) $data['qty']))),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => $this->canEditItems())
                    ->using(fn (SalesOrderItem $record, array $data) => static::runOrderAction(fn () => app(SalesOrderService::class)
                        ->updateItem($record, auth()->user(), (float) $data['qty']))),
                DeleteAction::make()
                    ->visible(fn () => $this->canEditItems())
                    ->using(fn (SalesOrderItem $record) => static::runOrderAction(fn () => app(SalesOrderService::class)
                        ->removeItem($record, auth()->user()) ?? true)),
            ]);
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
    }
}
