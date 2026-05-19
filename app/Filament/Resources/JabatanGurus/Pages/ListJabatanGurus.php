<?php

namespace App\Filament\Resources\JabatanGurus\Pages;

use App\Filament\Resources\JabatanGurus\JabatanGuruResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJabatanGurus extends ListRecords
{
    protected static string $resource = JabatanGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Jabatan')
                ->icon('heroicon-o-plus'),
        ];
    }
}
