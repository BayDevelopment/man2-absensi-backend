<?php

namespace App\Filament\Resources\Angkatans\Pages;

use App\Filament\Resources\Angkatans\AngkatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAngkatans extends ListRecords
{
    protected static string $resource = AngkatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Angkatan')
                ->icon('heroicon-o-plus'),
        ];
    }
}
