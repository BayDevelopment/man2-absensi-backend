<?php

namespace App\Filament\Resources\Pengumumen\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PengumumenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->judul)
                    ->weight('semibold'),

                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                // ── Status Aktif ─────────────────────────────────────────
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                // ── Tanggal Publish ──────────────────────────────────────
                TextColumn::make('published_at')
                    ->label('Publish')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Langsung tayang')
                    ->toggleable(),

                // ── Tanggal Kedaluwarsa ──────────────────────────────────
                TextColumn::make('expired_at')
                    ->label('Kedaluwarsa')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Tidak ada batas')
                    ->toggleable(),

                // ── Dibuat / Diperbarui ───────────────────────────────────
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                SelectFilter::make('dibuat_oleh')
                    ->label('Dibuat Oleh')
                    ->relationship('dibuatOleh', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('sudah_publish')
                    ->label('Sudah Ditayangkan')
                    ->query(fn(Builder $query) => $query->where(function ($q) {
                        $q->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    }))
                    ->toggle(),

                // ── Filter: Belum Expired ─────────────────────────────────
                Filter::make('belum_expired')
                    ->label('Belum Kedaluwarsa')
                    ->query(fn(Builder $query) => $query->where(function ($q) {
                        $q->whereNull('expired_at')
                            ->orWhere('expired_at', '>', now());
                    }))
                    ->toggle(),

                // ── Filter: Rentang Tanggal Publish ──────────────────────
                Filter::make('published_at_range')
                    ->label('Rentang Publish')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari')
                            ->native(false)
                            ->displayFormat('d M Y'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai')
                            ->native(false)
                            ->displayFormat('d M Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) => $q->whereDate('published_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) => $q->whereDate('published_at', '<=', $data['until'])
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $indicators;
                    }),

            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
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
