<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Filament\Concerns\HandlesOrderErrors;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    use HandlesOrderErrors;

    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isOwnerDraft = fn (SalesOrder $record) => $record->isDraft() && $user->isSalesAdmin() && $record->isOwnedBy($user);
        $isPending = fn (SalesOrder $record) => $record->status === SalesOrder::SUBMITTED && $user->isSupervisor();

        return [
            Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Setelah di-submit, pesanan tidak dapat diubah lagi.')
                ->visible($isOwnerDraft)
                ->action(function (SalesOrder $record) use ($user) {
                    static::runOrderAction(fn () => app(SalesOrderService::class)->submit($record, $user));
                    $this->done('Pesanan di-submit');
                }),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($isPending)
                ->schema([
                    Textarea::make('note')->label('Catatan (opsional)')->maxLength(1000),
                ])
                ->action(function (SalesOrder $record, array $data) use ($user) {
                    static::runOrderAction(fn () => app(SalesOrderService::class)->approve($record, $user, $data['note'] ?? null));
                    $this->done('Pesanan disetujui');
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($isPending)
                ->schema([
                    Textarea::make('note')->label('Catatan')->required()->maxLength(1000),
                ])
                ->action(function (SalesOrder $record, array $data) use ($user) {
                    static::runOrderAction(fn () => app(SalesOrderService::class)->reject($record, $user, $data['note'] ?? null));
                    $this->done('Pesanan ditolak, reservasi stok dilepas');
                }),

            Action::make('deleteDraft')
                ->label('Hapus Draft')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->requiresConfirmation()
                ->visible($isOwnerDraft)
                ->action(function (SalesOrder $record) use ($user) {
                    static::runOrderAction(fn () => app(SalesOrderService::class)->deleteDraft($record, $user));
                    Notification::make()->success()->title('Draft dihapus, reservasi stok dilepas')->send();
                    $this->redirect(SalesOrderResource::getUrl('index'));
                }),
        ];
    }

    private function done(string $message): void
    {
        Notification::make()->success()->title($message)->send();
        $this->redirect(SalesOrderResource::getUrl('view', ['record' => $this->getRecord()]));
    }
}
