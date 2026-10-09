<?php

namespace App\Filament\Resources\SalesOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// Audit trail (FR-8): hanya baca, kronologis.
class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit Trail';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i:s'),
                TextColumn::make('user.name')->label('Pengguna'),
                TextColumn::make('action')->label('Aksi')->badge(),
                TextColumn::make('old_values')->label('Sebelum')->formatStateUsing(fn ($state) => self::kv($state))->wrap(),
                TextColumn::make('new_values')->label('Sesudah')->formatStateUsing(fn ($state) => self::kv($state))->wrap(),
                TextColumn::make('note')->label('Catatan')->wrap()->placeholder('-'),
            ])
            ->paginated(false);
    }

    private static function kv(mixed $state): string
    {
        $values = is_array($state) ? $state : (json_decode((string) $state, true) ?? []);

        return collect($values)->map(fn ($v, $k) => "{$k}: ".($v ?? '-'))->implode(', ');
    }
}
