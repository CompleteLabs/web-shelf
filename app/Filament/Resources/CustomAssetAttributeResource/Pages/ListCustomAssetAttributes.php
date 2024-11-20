<?php

namespace App\Filament\Resources\CustomAssetAttributeResource\Pages;

use App\Filament\Resources\CustomAssetAttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomAssetAttributes extends ListRecords
{
    protected static string $resource = CustomAssetAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
