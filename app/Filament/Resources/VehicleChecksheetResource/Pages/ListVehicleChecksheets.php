<?php

namespace App\Filament\Resources\VehicleChecksheetResource\Pages;

use App\Filament\Resources\VehicleChecksheetResource;
use App\Imports\VehicleChecksheetImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleChecksheets extends ListRecords
{
    protected static string $resource = VehicleChecksheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
            ->color("success")
            ->use(VehicleChecksheetImport::class),
            Actions\CreateAction::make(),
        ];
    }
}
