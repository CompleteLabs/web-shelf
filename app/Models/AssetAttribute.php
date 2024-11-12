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
        'attribute_key',
        'attribute_value',
    ];

    protected static function booted()
    {
        static::creating(function ($assetAttribute) {
            // Ubah attribute_key menjadi snake_case
            if ($assetAttribute->attribute_key) {
                $assetAttribute->attribute_key = strtolower(str_replace(' ', '_', $assetAttribute->attribute_key));
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
