<?php

namespace App\Filament\Resources\GuruMapels\Pages;

use App\Filament\Resources\GuruMapels\GuruMapelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGuruMapels extends ListRecords
{
    protected static string $resource = GuruMapelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Guru Mapel')
                ->icon('heroicon-o-plus'),
        ];
    }
}
