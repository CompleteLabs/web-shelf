<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers\AssetTransfersRelationManager;
use App\Models\Asset;
use App\Models\AssetAttribute;
use App\Models\AssetLocation;
use App\Models\Brand;
use App\Models\BusinessEntity;
use App\Models\Category;
use App\Models\CustomAssetAttribute;
use App\Models\User;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as ComponentsGrid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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

    // Fungsi helper untuk mendapatkan tipe atribut
    function getCustomAttributeType($customAttributeId)
    {
        $customAttribute = CustomAssetAttribute::find($customAttributeId);
        return $customAttribute ? $customAttribute->type : null;
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
                                // Dropdown untuk memilih kategori
                                Select::make('category_id')
                                    ->label(__('Kategori'))
                                    ->options(self::getCategoryOptions())
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('attributes', []);
                                            $categoryId = is_array($state) ? $state : [$state];
                                            $attributes = CustomAssetAttribute::where('is_active', true)
                                                ->where(function ($query) use ($categoryId) {
                                                    foreach ($categoryId as $id) {
                                                        $query->orWhereJsonContains('category_id', (int) $id); // Pastikan integer
                                                    }
                                                    $query->orWhereJsonLength('category_id', 0);
                                                })
                                                ->get()
                                                ->map(function ($attribute) {
                                                    return [
                                                        'custom_attribute_id' => $attribute->id,
                                                        'attribute_value' => '',
                                                    ];
                                                })
                                                ->toArray();
                                            $set('attributes', $attributes);
                                        }
                                    }),

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

                                // Input untuk nama
                                TextInput::make('name')
                                    ->label(__('Nama'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                            ])
                            ->columns(2),

                        Repeater::make('attributes')
                            ->relationship('attributes')
                            ->schema([
                                // Dropdown untuk memilih atribut
                                Select::make('custom_attribute_id')
                                    ->label(__('Atribut'))
                                    ->options(function (callable $get) {
                                        $categoryId = $get('../../category_id');
                                        $selectedId = $get('../custom_attribute_id');
                                        if ($categoryId) {
                                            $categoryId = is_array($categoryId) ? $categoryId : [$categoryId];
                                            // Ambil atribut yang sudah dipilih di semua entri repeater
                                            $selectedAttributes = collect($get('../attributes'))
                                                ->pluck('custom_attribute_id')
                                                ->filter()
                                                ->toArray();
                                            // Filter atribut yang sesuai dengan kategori dan belum dipilih
                                            $attributes = CustomAssetAttribute::where('is_active', true)
                                                ->where(function ($query) use ($categoryId) {
                                                    foreach ($categoryId as $id) {
                                                        $query->orWhereJsonContains('category_id', (int) $id);
                                                    }
                                                    $query->orWhereJsonLength('category_id', 0);
                                                })
                                                ->whereNotIn('id', $selectedAttributes) // Pastikan atribut yang sudah dipilih tidak muncul lagi
                                                ->pluck('name', 'id')
                                                ->toArray();
                                            return $attributes;
                                        }

                                        return [];
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->required()
                                    ->afterStateHydrated(function ($state, callable $set) {
                                        if ($state) {
                                            // Ambil nama atribut berdasarkan ID
                                            $customAttribute = CustomAssetAttribute::find($state);

                                            if ($customAttribute) {
                                                // Tetapkan ID untuk backend dan nama untuk dropdown
                                                $set('custom_attribute_id', $customAttribute->id);
                                                $set('custom_attribute_label', $customAttribute->name);
                                            }
                                        }
                                    }),

                                // Input untuk nilai atribut
                                TextInput::make('attribute_value')
                                    ->label(__('Nilai Atribut'))
                                    ->reactive()
                                    ->visible(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->type === 'text')
                                    ->afterStateHydrated(function ($state, callable $set) {
                                        $set('attribute_value', $state ?? '');
                                    }),

                                // Input numerik
                                TextInput::make('attribute_value')
                                    ->label(__('Nilai Atribut'))
                                    ->required(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->required)
                                    ->numeric()
                                    ->reactive()
                                    ->visible(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->type === 'number')
                                    ->afterStateHydrated(function ($state, callable $set) {
                                        $set('attribute_value', $state ?? '');
                                    }),

                                // Input untuk textarea
                                Textarea::make('attribute_value')
                                    ->label(__('Nilai Atribut'))
                                    ->required(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->required)
                                    ->reactive()
                                    ->visible(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->type === 'textarea')
                                    ->afterStateHydrated(function ($state, callable $set) {
                                        $set('attribute_value', $state ?? '');
                                    }),

                                // Input untuk date picker
                                DatePicker::make('attribute_value')
                                    ->label(__('Nilai Atribut'))
                                    ->required(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->required)
                                    ->reactive()
                                    ->visible(fn(callable $get) => $get('custom_attribute_id') && CustomAssetAttribute::find($get('custom_attribute_id'))->type === 'date')
                                    ->afterStateHydrated(function ($state, callable $set) {
                                        $set('attribute_value', $state ?? '');
                                    }),
                            ])
                            ->columns(2)
                            ->columnSpan(2)
                            ->visible(fn(callable $get) => $get('category_id') !== null)
                            ->afterStateHydrated(function ($state, callable $set, $record) {
                                if ($record && $record->attributes) {
                                    $state = [];
                                    foreach ($record->attributes as $attribute) {
                                        $customAttribute = CustomAssetAttribute::find($attribute->custom_attribute_id);
                                        $state[] = [
                                            'custom_attribute_id' => $attribute->custom_attribute_id,
                                            'custom_attribute_label' => $customAttribute ? $customAttribute->name : null,
                                            'attribute_value' => $attribute->attribute_value,
                                        ];
                                    }
                                    $set('attributes', $state);
                                }
                            }),


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
                            ->default(1)
                            ->required()
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
                        FileUpload::make('image')
                            ->label('Gambar Aset')
                            ->directory('assets') // Define the directory to store images
                            ->image() // Only allow image uploads
                            ->maxSize(2048)
                            ->resize(50),
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
                TextColumn::make('item_age')
                    ->translateLabel()
                    ->sortable(query: fn($query, $direction) => $query->sortByItemAge($direction)),
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
                BulkAction::make('pindahkanKeAtribut')
                    ->label('Pindahkan ke Atribut')
                    ->action(fn(Collection $records) => self::pindahkanKeAssetAttributeBulk($records))
                    ->requiresConfirmation()
                    ->color('primary')
                    ->icon('heroicon-o-arrow-right'), // Ikon untuk bulk action
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
                        ComponentsGrid::make(2)
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
                                    ->label(__('Gambar Aset'))
                                    ->width('100px')
                                    ->height('100px'),
                            ]),
                    ])
                    ->columnSpan(2)
                    ->grow(true),

                ComponentsSection::make('⚙️ Atribut Khusus')
                    ->schema([
                        ComponentsGrid::make(2)
                            ->schema(function ($record) {
                                // Pastikan $record memiliki relasi 'attributes'
                                return $record->attributes->map(function ($attribute) {
                                    return TextEntry::make("custom_attribute_{$attribute->custom_attribute_id}")
                                        ->label(optional($attribute->customAttribute)->name ?? 'Unknown Attribute') // Langsung mengambil nama dari customAttribute
                                        ->state($attribute->attribute_value); // Nilai dari attribute_value
                                })->toArray();
                            }),
                    ]),

                ComponentsSection::make('🛒 Detail Pembelian')
                    ->schema([
                        ComponentsGrid::make(4)
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
                        ComponentsGrid::make(4)
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
                                    ->label(__('✅ Status Validasi'))
                                    ->badge()
                                    ->color(fn($record) => $record->checkValidRecipient() ? 'success' : 'danger')
                                    ->formatStateUsing(function ($record) {
                                        return $record->checkValidRecipient() ? 'Valid' : 'Tidak Valid';
                                    }),
                            ]),
                    ]),
            ])
            ->columns(1); // Atur agar semua bagian ditampilkan secara vertikal (atas-bawah)
    }

    // In your AssetAttribute model
    protected static function pindahkanKeAssetAttributeBulk($records)
    {
        foreach ($records as $record) {
            // Daftar kolom yang akan dipindahkan sebagai `attribute_key` dan `attribute_value`
            $attributes = [
                '3' => $record->serial_number,
                '1' => $record->imei1,
                '2' => $record->imei2,
            ];

            foreach ($attributes as $key => $value) {
                // Pastikan hanya memindahkan jika $value tidak null atau kosong
                if (!is_null($value) && $value !== '') {
                    AssetAttribute::updateOrCreate(
                        [
                            'asset_id' => $record->id,
                            'custom_attribute_id' => $key,
                        ],
                        [
                            'attribute_value' => $value,
                        ]
                    );
                }
            }
        }

        Notification::make()
            ->title('Sukses')
            ->body('Atribut berhasil dipindahkan ke AssetAttribute untuk aset yang dipilih.')
            ->success()
            ->send();
    }
}
