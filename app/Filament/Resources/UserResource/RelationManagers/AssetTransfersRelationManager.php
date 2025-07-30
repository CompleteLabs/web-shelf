<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\AssetTransfer;
use App\Models\AssetTransferDetail;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class AssetTransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'assetTransferDetails';

    public function getTableQuery(): Builder
    {
        $userId = $this->getOwnerRecord()->id;

        // Get all AssetTransferDetails where the user is either sender or receiver
        return AssetTransferDetail::whereHas('assetTransfer', function($query) use ($userId) {
            $query->where('from_user_id', $userId)
                  ->orWhere('to_user_id', $userId);
        });
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('assetTransfer.letter_number'),
                TextInput::make('assetTransfer.fromUser.name'),
                TextInput::make('assetTransfer.toUser.name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('letter_number')
            ->columns([
                TextColumn::make('assetTransfer.letter_number')
                    ->translateLabel()
                    ->badge(),
                TextColumn::make('assetTransfer.fromUser.name')
                    ->translateLabel()
                    ->badge()
                    ->color('danger'),
                TextColumn::make('assetTransfer.toUser.name')
                    ->translateLabel()
                    ->badge()
                    ->color('success'),
                TextColumn::make('transfer_type')
                    ->label('Peran User')
                    ->badge()
                    ->colors([
                        'primary' => 'Pemberi',
                        'success' => 'Penerima',
                    ])
                    ->getStateUsing(function ($record) {
                        $currentUserId = $this->getOwnerRecord()->id;
                        return $record->assetTransfer->from_user_id == $currentUserId ? 'Pemberi' : 'Penerima';
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'BERITA ACARA SERAH TERIMA',
                        'success' => 'BERITA ACARA PENGALIHAN BARANG',
                        'danger' => 'BERITA ACARA PENGEMBALIAN BARANG',
                        'secondary' => 'Unknown Status',
                    ])
                    ->getStateUsing(function ($record) {
                        return $record->assetTransfer->status;
                    }),
                TextColumn::make('assetTransfer.document')
                    ->url(fn ($record) => $record && $record->assetTransfer && $record->assetTransfer->document ? Storage::url($record->assetTransfer->document) : null, true)
                    ->openUrlInNewTab()
                    ->translateLabel()
                    ->getStateUsing(fn ($record) => $record->assetTransfer && $record->assetTransfer->document ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text'),
                TextColumn::make('assetTransfer.created_at')
                    ->date()
                    ->label(__('Created at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('transfer_type')
                    ->label('Peran User')
                    ->options([
                        'sender' => 'Sebagai Pemberi',
                        'receiver' => 'Sebagai Penerima',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        $currentUserId = $this->getOwnerRecord()->id;

                        return $query->whereHas('assetTransfer', function (Builder $q) use ($data, $currentUserId) {
                            if ($data['value'] === 'sender') {
                                $q->where('from_user_id', $currentUserId);
                            } elseif ($data['value'] === 'receiver') {
                                $q->where('to_user_id', $currentUserId);
                            }
                        });
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('createAssetTransfer')
                    ->label('Transfer Asset')
                    ->url(route('filament.admin.resources.asset-transfers.create'))
                    ->icon('heroicon-o-plus')
                    ->color('success')
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
