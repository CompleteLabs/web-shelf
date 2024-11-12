<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleChecksheetResource\Pages;
use App\Filament\Resources\VehicleChecksheetResource\RelationManagers;
use App\Models\VehicleChecksheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class VehicleChecksheetResource extends Resource
{
    protected static ?string $model = VehicleChecksheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Informasi Kendaraan
                Forms\Components\Section::make('Informasi Kendaraan')
                    ->schema([
                        Forms\Components\Select::make('asset_id')
                            ->relationship('asset', 'name')
                            ->required()
                            ->label('Nama Aset'),
                            Forms\Components\TextInput::make('reference_number')
                            ->required()
                            ->maxLength(255)
                            ->label('Nomor Referensi')
                            ->disabled() // Membuat field read-only agar tidak bisa diubah oleh pengguna
                            ->default(function () {
                                return VehicleChecksheetResource::generateReferenceNumber();
                            }),
                        Forms\Components\TextInput::make('license_plate')
                            ->required()
                            ->maxLength(255)
                            ->label('Plat Nomor'),
                        Forms\Components\TextInput::make('pic')
                            ->maxLength(255)
                            ->label('PIC (Penanggung Jawab)'),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->label('Lokasi Kendaraan')
                            ->placeholder('Contoh: Depo 1, Workshop, dll.'),
                    ]),

                // Informasi Keberangkatan
                Forms\Components\Section::make('Informasi Keberangkatan')
                    ->schema([
                        Forms\Components\TextInput::make('start_km')
                            ->numeric()
                            ->label('Kilometer Awal')
                            ->placeholder('Masukkan KM awal'),
                        Forms\Components\DateTimePicker::make('departure_time')
                            ->label('Waktu Keberangkatan'),
                        Forms\Components\FileUpload::make('departure_photo')
                            ->image()
                            ->maxSize(1024)
                            ->label('Foto Keberangkatan'),
                        Forms\Components\FileUpload::make('departure_damage_report')
                            ->image()
                            ->maxSize(1024)
                            ->label('Laporan Kerusakan Saat Keberangkatan'),
                    ]),

                // Informasi Pengembalian
                Forms\Components\Section::make('Informasi Pengembalian')
                    ->schema([
                        Forms\Components\TextInput::make('end_km')
                            ->numeric()
                            ->label('Kilometer Akhir')
                            ->placeholder('Masukkan KM akhir'),
                        Forms\Components\DateTimePicker::make('return_time')
                            ->label('Waktu Pengembalian'),
                        Forms\Components\FileUpload::make('return_photo')
                            ->image()
                            ->maxSize(1024)
                            ->label('Foto Pengembalian'),
                        Forms\Components\FileUpload::make('return_damage_report')
                            ->image()
                            ->maxSize(1024)
                            ->label('Laporan Kerusakan Saat Pengembalian'),
                    ]),

                // Informasi Tambahan
                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('rental_duration')
                            ->numeric()
                            ->label('Durasi Sewa (jam)')
                            ->disabled(), // Set as read-only
                        Forms\Components\TextInput::make('distance_traveled')
                            ->numeric()
                            ->default(0.00)
                            ->label('Jarak Tempuh')
                            ->disabled(), // Set as read-only
                        Forms\Components\Textarea::make('remarks')
                            ->columnSpanFull()
                            ->label('Catatan Tambahan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pic')
                    ->searchable(),
                Tables\Columns\TextColumn::make('license_plate')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_km')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departure_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departure_photo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departure_damage_report')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_km')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('return_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('return_photo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('return_damage_report')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rental_duration')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distance_traveled')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Panggil fungsi generateReferenceNumber untuk menghasilkan nomor referensi
        $data['reference_number'] = self::generateReferenceNumber();

        return $data;
    }

    protected static function generateReferenceNumber(): string
    {
        $year = date('Y');
        $latestRecord = VehicleChecksheet::whereYear('created_at', $year)
            ->orderByDesc('reference_number')
            ->first();

        if ($latestRecord) {
            // Ambil nomor urut terakhir dan tambahkan 1
            $lastNumber = (int) Str::afterLast($latestRecord->reference_number, '-');
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // Jika belum ada record untuk tahun ini, mulai dari 001
            $newNumber = '001';
        }

        // Format akhir menjadi GA-{tahun}-{nomor urut tiga digit}
        return "GA-{$year}-{$newNumber}";
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
            'index' => Pages\ListVehicleChecksheets::route('/'),
            'create' => Pages\CreateVehicleChecksheet::route('/create'),
            'edit' => Pages\EditVehicleChecksheet::route('/{record}/edit'),
        ];
    }
}
