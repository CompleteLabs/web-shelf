<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'custom_attribute_id',
        'attribute_value',
    ];

    // Relasi ke aset
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Relasi ke custom attribute
    public function customAttribute()
    {
        return $this->belongsTo(CustomAssetAttribute::class, 'custom_attribute_id');
    }
}
