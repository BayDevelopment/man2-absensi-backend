<?php

namespace App\Filament\Resources\Siswas\Tables;

use App\Filament\Actions\RegisterFaceAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NISN')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable()
                    ->default('Belum ada'),

                BadgeColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->colors([
                        'primary'   => 'L',
                        'secondary' => 'P',
                    ])
                    ->getStateUsing(fn($record) => $record->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan')
                    ->sortable(),

                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->sortable(),

                TextColumn::make('foto')
                    ->label('Foto')
                    ->getStateUsing(function ($record) {
                        if ($record && filled($record->foto)) {
                            $url = asset('storage/' . $record->foto);
                            return '<img src="' . e($url) . '" 
                                        style="width:40px;height:40px;border-radius:50%;object-fit:cover;" 
                                        alt="foto" />';
                        }
                        return '<span style="color:red;font-size:12px;font-weight:500;">Belum ada foto</span>';
                    })
                    ->html()
                    ->toggleable(),

                IconColumn::make('is_face_registered')
                    ->label('Face Terdaftar')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama_kelas'),

                SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Aktif'),
            ])
            ->defaultSort('nama_lengkap', 'asc')
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
                    RegisterFaceAction::make(),
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
