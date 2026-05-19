<?php

namespace App\Filament\Resources\Angkatans\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AngkatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Angkatan')
                    ->icon('heroicon-o-academic-cap')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn($record) => 'Masuk: ' . $record->tahun_masuk . ($record->tahun_lulus ? ' · Lulus: ' . $record->tahun_lulus : ' · Belum lulus'))
                    ->copyable()
                    ->copyMessage('Nama angkatan disalin!')
                    ->tooltip('Klik untuk menyalin'),

                TextColumn::make('tahun_masuk')
                    ->label('Tahun Masuk')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('tahun_lulus')
                    ->label('Tahun Lulus')
                    ->icon('heroicon-o-arrow-left-circle')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->placeholder('Belum Lulus')
                    ->default('-'),

                TextColumn::make('durasi')
                    ->label('Durasi')
                    ->icon('heroicon-o-clock')
                    ->state(function ($record): string {
                        if (! $record->tahun_lulus) {
                            $durasi = now()->year - $record->tahun_masuk;
                            return $durasi . ' tahun (berjalan)';
                        }
                        $durasi = $record->tahun_lulus - $record->tahun_masuk;
                        return $durasi . ' tahun';
                    })
                    ->badge()
                    ->color(fn($record) => $record->tahun_lulus ? 'gray' : 'warning'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->icon('heroicon-o-calendar')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->icon('heroicon-o-pencil-square')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false)
                    ->indicator('Status'),

                Filter::make('tahun_masuk')
                    ->label('Tahun Masuk')
                    ->indicator('Tahun Masuk')
                    ->form([
                        TextInput::make('tahun_masuk_dari')
                            ->label('Dari Tahun')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(now()->year + 1)
                            ->placeholder('Contoh: 2020'),

                        TextInput::make('tahun_masuk_sampai')
                            ->label('Sampai Tahun')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(now()->year + 1)
                            ->placeholder('Contoh: 2026'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tahun_masuk_dari'],
                                fn(Builder $q) => $q->where('tahun_masuk', '>=', $data['tahun_masuk_dari'])
                            )
                            ->when(
                                $data['tahun_masuk_sampai'],
                                fn(Builder $q) => $q->where('tahun_masuk', '<=', $data['tahun_masuk_sampai'])
                            );
                    }),

                Filter::make('tahun_lulus')
                    ->label('Tahun Lulus')
                    ->indicator('Tahun Lulus')
                    ->form([
                        TextInput::make('tahun_lulus_dari')
                            ->label('Dari Tahun')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(now()->year + 10)
                            ->placeholder('Contoh: 2024'),

                        TextInput::make('tahun_lulus_sampai')
                            ->label('Sampai Tahun')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(now()->year + 10)
                            ->placeholder('Contoh: 2030'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tahun_lulus_dari'],
                                fn(Builder $q) => $q->where('tahun_lulus', '>=', $data['tahun_lulus_dari'])
                            )
                            ->when(
                                $data['tahun_lulus_sampai'],
                                fn(Builder $q) => $q->where('tahun_lulus', '<=', $data['tahun_lulus_sampai'])
                            );
                    }),

                Filter::make('belum_lulus')
                    ->label('Belum Lulus')
                    ->indicator('Belum Lulus')
                    ->query(fn(Builder $query) => $query->whereNull('tahun_lulus'))
                    ->toggle(),
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
