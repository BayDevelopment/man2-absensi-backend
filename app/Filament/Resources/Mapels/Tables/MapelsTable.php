<?php

namespace App\Filament\Resources\Mapels\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class MapelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                // Nama Mapel
                TextColumn::make('nama')
                    ->label('Nama Mata Pelajaran')
                    ->searchable()
                    ->sortable(),

                // Kode Mapel
                TextColumn::make('kode')
                    ->label('Kode Mapel')
                    ->searchable()
                    ->sortable(),

                // Tanggal dibuat
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                // Tanggal diupdate
                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Filter bisa misal berdasarkan nama mapel atau kode
                Filter::make('nama')
                    ->query(fn($query, $value) => $query->where('nama', 'like', "%{$value}%"))
                    ->label('Cari Nama Mapel'),

                Filter::make('kode')
                    ->query(fn($query, $value) => $query->where('kode', 'like', "%{$value}%"))
                    ->label('Cari Kode Mapel'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus data?')
                        ->modalDescription('Data akan dipindahkan ke trash.')
                        ->successNotification(
                            Notification::make()
                                ->title('Berhasil')
                                ->body('Data berhasil dihapus')
                                ->success()
                        ),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->outlined(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
