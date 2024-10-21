<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers\AssetTransfersRelationManager;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\Brand;
use App\Models\BusinessEntity;
use App\Models\Category;
use App\Models\User;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as ComponentsGrid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function getCategoryOptions()
    {
        $categories = Category::with('children')->get();

        $options = [];
        foreach ($categories as $category) {
            if ($category->children->isNotEmpty()) {
                $subcategories = $category->children->pluck('name', 'id')->toArray();
                $options[$category->name] = $subcategories;
            }
        }

        return $options;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        // Kolom kiri
                        Card::make()
                            ->schema([
                                Select::make('category_id')
                                    ->translateLabel()
                                    ->options(self::getCategoryOptions())
                                    ->searchable()
                                    ->required(),
                                Select::make('brand_id')
                                    ->translateLabel()
                                    ->options(Brand::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                    ])
                                    ->createOptionUsing(function ($data) {
                                        // Create a new AssetLocation using the data from the form
                                        $brand = Brand::create([
                                            'name' => $data['name'],
                                        ]);

                                        // Return the ID of the newly created asset location
                                        return $brand->id;
                                    }),
                                TextInput::make('type')
                                    ->translateLabel()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->translateLabel('Nama')
                                    ->required()
                                    ->maxLength(255),
                                FileUpload::make('image')
                                    ->label('Gambar Aset')
                                    ->directory('assets') // Define the directory to store images
                                    ->image() // Only allow image uploads
                                    ->maxSize(2048) // Maximum size (optional)
                                    ->columnSpan(2),
                            ])
                            ->columns(2),

                        Card::make()
                            ->schema([
                                TextInput::make('serial_number')
                                    ->translateLabel()
                                    ->maxLength(255),
                                TextInput::make('imei1')
                                    ->translateLabel()
                                    ->maxLength(255),
                                TextInput::make('imei2')
                                    ->translateLabel()
                                    ->maxLength(255),
                            ])
                            ->columns(3),

                        // Super Admin
                        Card::make()
                            ->schema([
                                Toggle::make('is_available')
                                    ->label('Status Ketersediaan')
                                    ->inline(false)
                                    ->onColor('success')
                                    ->offColor('danger'),
                                Select::make('recipient_business_entity_id')
                                    ->translateLabel()
                                    ->options(BusinessEntity::orderBy('name')->pluck('name', 'id'))
                                    ->searchable(),
                                Select::make('recipient_id')
                                    ->translateLabel()
                                    ->options(User::orderBy('name')->pluck('name', 'id'))
                                    ->searchable(),
                            ])
                            ->columns(3)
                            ->visible(fn() => auth()->user()->hasRole('super_admin')),
                    ])
                    ->columnSpan(2),
                // Kolom kanan
                Card::make()
                    ->schema([
                        DatePicker::make('purchase_date')
                            ->translateLabel()
                            ->required(),
                        Select::make('business_entity_id')
                            ->translateLabel()
                            ->options(BusinessEntity::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('item_price')
                            ->translateLabel()
                            ->numeric(),
                        TextInput::make('qty')
                            ->translateLabel()
                            ->numeric(),
                        Select::make('asset_location_id')
                            ->translateLabel()
                            ->options(AssetLocation::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->translateLabel()
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('address')
                                    ->translateLabel()
                                    ->maxLength(255),
                                TextInput::make('description')
                                    ->translateLabel()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function ($data) {
                                // Create a new AssetLocation using the data from the form
                                $assetLocation = AssetLocation::create([
                                    'name' => $data['name'],
                                    'address' => $data['address'],
                                    'description' => $data['description'],
                                ]);

                                // Return the ID of the newly created asset location
                                return $assetLocation->id;
                            }),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_date')->translateLabel()->date()->sortable(),
                TextColumn::make('businessEntity.name') // Mengambil nama dari relasi businessEntity
                    ->translateLabel()
                    ->badge()
                    ->color(fn($record) => $record->businessEntity->color)
                    ->getStateUsing(fn($record) => $record->businessEntity->name),
                TextColumn::make('name')->translateLabel()->sortable()->searchable(),
                TextColumn::make('category.name')->translateLabel()->sortable(),
                TextColumn::make('brand.name')->translateLabel()->sortable()->searchable(),
                TextColumn::make('type')->translateLabel()->sortable()->searchable(),
                TextColumn::make('serial_number')->translateLabel()->sortable()->searchable(),
                TextColumn::make('imei1')->translateLabel()->sortable()->searchable(),
                TextColumn::make('imei2')->translateLabel()->sortable()->searchable(),
                TextColumn::make('item_price')->translateLabel()->sortable()->money('IDR', true),
                TextColumn::make('item_age')->translateLabel()->sortable(),
                TextColumn::make('qty') // Mengambil nama dari relasi businessEntity
                    ->translateLabel()
                    ->badge(),
                TextColumn::make('assetLocation.name')->translateLabel()->sortable()->searchable(),
                TextColumn::make('is_available')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => $state == 'Tersedia' ? 'success' : 'warning')
                    ->formatStateUsing(fn(string $state): string => $state),
            ])
            ->filters([
                SelectFilter::make('businessEntity')->relationship('businessEntity', 'name')->translateLabel(),
                SelectFilter::make('is_available')
                    ->translateLabel()
                    ->options([
                        '1' => 'Tersedia',
                        '0' => 'Transfer',
                    ])
                    ->translateLabel(),
                SelectFilter::make('assetLocation')->relationship('assetLocation', 'name')->translateLabel(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssetTransfersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
            'view' => Pages\ViewAsset::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Asset');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Assets');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ComponentsSection::make('💼 Informasi Aset')
                    ->schema([
                        ComponentsGrid::make(2)  // Grid untuk informasi aset menggunakan 2 kolom
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('📝 Nama Aset'))
                                    ->columnSpan(2)
                                    ->extraAttributes([
                                        'style' => 'font-weight:bold; font-size:1.2em; color:#333;',
                                    ]),
                                TextEntry::make('category.name')
                                    ->label(__('📂 Kategori')),
                                TextEntry::make('brand.name')
                                    ->label(__('🏷️ Merek')),
                                TextEntry::make('type')
                                    ->label(__('🔖 Tipe')),
                                ImageEntry::make('image')
                                    ->label('Gambar Aset')
                                    ->width('100px') // Set width
                                    ->height('100px'), // Set height
                                TextEntry::make('serial_number')
                                    ->label(__('🔢 Nomor Seri')),
                                TextEntry::make('imei1')
                                    ->label(__('📱 IMEI 1')),
                                TextEntry::make('imei2')
                                    ->label(__('📱 IMEI 2')),
                            ]),
                    ])
                    ->columnSpan(2)
                    ->grow(true),  // Bagian Informasi Aset tetap menggunakan dua kolom penuh

                ComponentsSection::make('🛒 Detail Pembelian')
                    ->schema([
                        ComponentsGrid::make(4)  // Grid untuk Detail Pembelian menggunakan 4 kolom horizontal
                            ->schema([
                                TextEntry::make('purchase_date')
                                    ->label(__('📅 Tanggal Pembelian'))
                                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y'))
                                    ->extraAttributes(['style' => 'color:#007BFF;']),
                                TextEntry::make('item_price')
                                    ->label(__('💰 Harga'))
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                    ->extraAttributes([
                                        'style' => 'color:#28a745; font-weight:bold;',
                                    ]),
                                TextEntry::make('qty')
                                    ->label(__('📦 Kuantitas')),
                                TextEntry::make('businessEntity.name')
                                    ->label(__('🏢 Entitas Bisnis')),
                            ]),
                    ]),

                ComponentsSection::make('🏠 Ketersediaan')
                    ->schema([
                        ComponentsGrid::make(4)  // Grid untuk Ketersediaan menggunakan 2 kolom
                            ->schema([
                                TextEntry::make('assetLocation.name')
                                    ->label(__('📍 Lokasi Aset')),
                                TextEntry::make('recipient.name')
                                    ->label(__('👤 Pemegang Aset')),
                                TextEntry::make('is_available')
                                    ->label(__('🟢 Status Ketersediaan'))
                                    ->formatStateUsing(fn(string $state): string => $state == 'Tersedia' ? 'Tersedia' : 'Transfer')
                                    ->badge()
                                    ->color(fn(string $state): string => $state == 'Tersedia' ? 'success' : 'warning')
                                    ->extraAttributes(['style' => 'font-weight:bold;']),
                                TextEntry::make('recipient.name')
                                    ->label(__('✅ Status Validasi'))  // Label untuk status validasi
                                    ->badge()  // Menambahkan badge
                                    ->color(fn($record) => $record->checkValidRecipient() ? 'success' : 'danger')  // Menentukan warna badge
                                    ->formatStateUsing(function ($record) {
                                        return $record->checkValidRecipient() ? 'Valid' : 'Tidak Valid';  // Menampilkan status validasi
                                    })
                            ]),
                    ]),
            ])
            ->columns(1);  // Atur agar semua bagian ditampilkan secara vertikal (atas-bawah)
    }
}
