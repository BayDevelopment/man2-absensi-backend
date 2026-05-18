<?php

namespace App\Filament\Resources\Mapels\Pages;

use App\Filament\Resources\Mapels\MapelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMapels extends ListRecords
{
    protected static string $resource = MapelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Mapel'),
        ];
    }
}
