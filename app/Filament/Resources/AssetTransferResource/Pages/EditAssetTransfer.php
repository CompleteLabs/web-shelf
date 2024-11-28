<?php

namespace App\Filament\Resources\AssetTransferResource\Pages;

use App\Filament\Resources\AssetTransferResource;
use App\Models\AssetTransfer;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditAssetTransfer extends EditRecord
{
    protected static string $resource = AssetTransferResource::class;


    protected function afterSave(): void
    {
        $record = $this->record;
        $toUser = $record->toUser;
        $hasGeneralAffairRole = $toUser->hasRole('general_affair');

        foreach ($record->details as $detail) {
            $asset = $detail->asset;
            $asset->recipient_id = $record->to_user_id;
            $asset->recipient_business_entity_id = $record->business_entity_id;
            $asset->is_available = $hasGeneralAffairRole ? true : false;
            $asset->save();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Action::make('download')
            ->label('Download PDF')
            ->url(fn (AssetTransfer $record): string => route('asset-transfer.download', $record))
            ->color('info'),
        ];
    }
}
