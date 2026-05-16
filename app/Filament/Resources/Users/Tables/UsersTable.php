<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'guru'  => 'warning',
                        'siswa' => 'success',
                    }),

                IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => !is_null($record->email_verified_at))
                    ->tooltip(fn($record) => $record->email_verified_at
                        ? 'Terverifikasi: ' . $record->email_verified_at->format('d M Y H:i')
                        : 'Belum terverifikasi'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'guru'  => 'Guru',
                        'siswa' => 'Siswa',
                    ]),

                SelectFilter::make('email_verified')
                    ->label('Status Email')
                    ->options([
                        'verified'   => 'Terverifikasi',
                        'unverified' => 'Belum Terverifikasi',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'verified') {
                            $query->whereNotNull('email_verified_at');
                        } elseif ($data['value'] === 'unverified') {
                            $query->whereNull('email_verified_at');
                        }
                    }),
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
                    Action::make('verify_email')
                        ->label('Verifikasi')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->visible(fn($record) => $record->email && is_null($record->email_verified_at))
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Verifikasi Email')
                        ->modalDescription(fn($record) => "Kirim email verifikasi ke {$record->email}?")
                        ->action(function ($record) {
                            $record->sendEmailVerificationNotification();

                            Notification::make()
                                ->title('Email verifikasi berhasil dikirim!')
                                ->success()
                                ->send();
                        }),
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
            ])
            ->defaultSort('created_at', 'desc');;
    }
}
