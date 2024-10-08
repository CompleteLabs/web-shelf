<?php

namespace App\Filament\Resources\AssetResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomAssetWidget extends BaseWidget
{
    protected function getStats(): array
    {
        Log::info('CustomAssetWidget getStats called');
        $availableUnits = Asset::where('is_available', true)->count();
        $transferredUnits = Asset::where('is_available', false)->count();
        $totalAssets = Asset::count();
        $totalValue = Asset::sum(DB::raw('item_price * qty'));

        return [
            Stat::make(__('Aset Tersedia'), $availableUnits)->color('success'),
            Stat::make(__('Aset Digunakan'), $transferredUnits)->color('warning'),
            Stat::make(__('Jumlah Aset'), $totalAssets)->color('primary'),
            Stat::make(__('Jumlah Nilai Aset'), 'IDR ' . number_format($totalValue))->color('primary'),
        ];
    }
}
