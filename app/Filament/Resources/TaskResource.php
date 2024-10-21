<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Navigation\MenuItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Umum')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Pekerjaan')
                                    ->required(),

                                TextInput::make('cost')
                                    ->label('Biaya')
                                    ->numeric() // Mengatur agar input hanya angka
                                    ->prefix('Rp ') // Tambahkan prefix 'Rp ' di depan input
                                    ->required(),
                            ]),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->required(),

                        TextInput::make('location')
                            ->label('Lokasi')
                            ->required(),
                    ]),

                // Vendor Information Section
                Forms\Components\Section::make('Informasi Vendor')
                    ->schema([
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Vendor')
                                    ->required(),

                                TextInput::make('last_price')
                                    ->label('Harga Terakhir (Rp)')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required()
                                    ->placeholder('Masukkan harga terakhir'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->numeric(),
                Tables\Columns\TextColumn::make('cost')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status') // Label in Indonesian
                    ->badge() // Enables badge display
                    ->colors([
                        'danger' => 'open',         // Red badge for 'open' status
                        'warning' => 'in_progress', // Yellow badge for 'in_progress' status
                        'success' => 'completed',   // Green badge for 'completed' status
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Group the custom actions together
                Tables\Actions\ActionGroup::make([
                    // Edit Action (with pencil icon)
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil') // Use the pencil icon for edit
                        ->visible(fn($record) => !in_array($record->status, ['in_progress', 'completed'])),

                    // Custom Process Action (color: blue, with play icon)
                    Tables\Actions\Action::make('process')
                        ->label('Process')
                        ->icon('heroicon-o-play') // Use the play icon for process
                        ->color('primary') // Use 'primary' for blue
                        ->visible(fn($record) => $record->status === 'open')
                        ->action(function ($record) {
                            $record->update(['status' => 'in_progress']);
                        }),

                    // Custom Complete Action (color: green, with check icon)
                    Tables\Actions\Action::make('complete')
                        ->label('Complete')
                        ->icon('heroicon-o-check-circle') // Use the check circle icon for complete
                        ->color('success') // Use 'success' for green
                        ->visible(fn($record) => $record->status === 'in_progress')
                        ->form([
                            FileUpload::make('attachment')
                                ->label('Upload Lampiran')
                                ->directory('task') // Define the directory to store images
                                ->image() // Only allow image uploads
                                ->maxSize(2048) // Maximum size (optional)
                                ->required()
                                ->multiple() // Enable multiple file uploads
                                ->maxFiles(5) // Optionally, limit the number of files (example: 5)
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'status' => 'completed',
                                'attachment' => $data['attachment'],
                            ]);
                        }),

                    // Custom Download Action (color: red, with download icon)
                    Tables\Actions\Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray') // Use the download icon for download
                        ->color('danger') // Use 'danger' for red
                        ->visible(fn($record) => $record->status === 'completed')
                        ->url(fn($record) => route('task-completion.download', $record->id))
                        ->openUrlInNewTab(),
                ])

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // General Information Section
                Section::make('Informasi Umum')
                    ->schema([
                        Grid::make(2) // Two-column grid layout for better spacing
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Tugas')
                                    ->visible(fn($record) => $record !== null),

                                TextEntry::make('vendor.name')
                                    ->label('Nama Vendor')
                                    ->visible(fn($record) => $record !== null),
                            ]),

                        TextEntry::make('cost')
                            ->label('Biaya')
                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 2))
                            ->visible(fn($record) => $record !== null),

                        TextEntry::make('location')
                            ->label('Lokasi')
                            ->visible(fn($record) => $record !== null),
                    ])
                    ->columns(1), // One column for full-width content (e.g., cost, location)

                // Status Section
                Section::make('Status Pekerjaan')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->visible(fn($record) => $record !== null),
                    ])
                    ->columns(1), // Single column for status information

                // Attachments Section
                Section::make('Lampiran')
                    ->schema([
                        TextEntry::make('attachment')
                            ->label('Lampiran')
                            ->formatStateUsing(function ($state) {
                                $baseUrl = asset(''); // Adjust this path as necessary

                                // If the state is a JSON-encoded string, decode it
                                if (is_string($state) && str_starts_with($state, '[')) {
                                    $state = json_decode($state, true); // Decode JSON string to array
                                }

                                // If the state is an array, display multiple images
                                if (is_array($state)) {
                                    return collect($state)->map(function ($image) use ($baseUrl) {
                                        return "<img src='{$baseUrl}/{$image}' alt='Lampiran' style='max-width: 100px; margin-right: 10px;'>";
                                    })->implode(''); // Combine all images into a single string
                                }

                                // If the state is a single image (string), display it directly
                                if (is_string($state) && !empty($state)) {
                                    return "<img src='{$baseUrl}/{$state}' alt='Lampiran' style='max-width: 100px;'>";
                                }

                                return 'Tidak ada lampiran'; // Fallback if no value is found
                            })
                            ->html(), // Enable HTML rendering for images
                    ]),

                // Timestamps Section
                Section::make('Tanggal')
                    ->schema([
                        Grid::make(2) // Two-column grid for created and updated timestamps
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime()
                                    ->visible(fn($record) => $record !== null),

                                TextEntry::make('updated_at')
                                    ->label('Diperbarui Pada')
                                    ->dateTime()
                                    ->visible(fn($record) => $record !== null),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
