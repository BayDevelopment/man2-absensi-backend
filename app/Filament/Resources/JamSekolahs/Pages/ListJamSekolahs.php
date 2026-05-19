<?php

namespace App\Filament\Resources\JamSekolahs\Pages;

use App\Filament\Resources\JamSekolahs\JamSekolahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJamSekolahs extends ListRecords
{
    protected static string $resource = JamSekolahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Jam sekolah')
                ->icon('heroicon-o-plus'),
        ];
    }
}
