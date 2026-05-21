<?php

namespace App\Filament\Resources\Appearances\Tables;

use App\Models\AppearanceModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppearancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tema')
                    ->label('Tema')
                    ->formatStateUsing(fn($state) => AppearanceModel::TEMA[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'light'  => 'warning',
                        'dark'   => 'gray',
                        'system' => 'info',
                        default  => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('bahasa')
                    ->label('Bahasa')
                    ->formatStateUsing(fn($state) => AppearanceModel::BAHASA[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'id'    => 'success',
                        'en'    => 'info',
                        default => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('ukuran_teks')
                    ->label('Ukuran Teks')
                    ->formatStateUsing(fn($state) => AppearanceModel::UKURAN_TEKS[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'small'  => 'gray',
                        'normal' => 'success',
                        'large'  => 'warning',
                        default  => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tema')
                    ->label('Tema')
                    ->options(AppearanceModel::TEMA),

                SelectFilter::make('bahasa')
                    ->label('Bahasa')
                    ->options(AppearanceModel::BAHASA),

                SelectFilter::make('ukuran_teks')
                    ->label('Ukuran Teks')
                    ->options(AppearanceModel::UKURAN_TEKS),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
