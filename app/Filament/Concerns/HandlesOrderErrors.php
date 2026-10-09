<?php

namespace App\Filament\Concerns;

use App\Exceptions\OrderException;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

trait HandlesOrderErrors
{
    // Jalankan aksi service; pelanggaran business rule tampil sebagai notifikasi dan aksi dihentikan.
    protected static function runOrderAction(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (OrderException $e) {
            Notification::make()
                ->danger()
                ->title($e->errorCode)
                ->body($e->getMessage())
                ->send();

            throw new Halt;
        }
    }
}
