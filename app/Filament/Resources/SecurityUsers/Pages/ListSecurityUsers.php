<?php

namespace App\Filament\Resources\SecurityUsers\Pages;

use App\Filament\Resources\SecurityUsers\SecurityUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSecurityUsers extends ListRecords
{
    protected static string $resource = SecurityUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Set Security')
                ->icon('heroicon-o-plus'),
        ];
    }
}
