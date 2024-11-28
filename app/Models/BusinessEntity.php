<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessEntity extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'format', 'color', 'letterhead'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    // Relasi ke tabel asset_transfers
    public function assetTransfers()
    {
        return $this->hasMany(AssetTransfer::class);
    }

    // Relasi ke tabel users
    public function users()
    {
        return $this->hasMany(AssetTransfer::class);
    }
}
