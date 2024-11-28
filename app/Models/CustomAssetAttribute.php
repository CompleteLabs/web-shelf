<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomAssetAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'required',
        'is_active',
        'category_id',
        'is_notifiable',
        'notification_type',
        'notification_offset',
        'fixed_notification_date',
    ];

    protected $casts = [
        'category_id' => 'array',
    ];

    public function setCategoryIdAttribute($value)
    {
        $this->attributes['category_id'] = json_encode(array_map('intval', $value));
    }

    // Relasi ke kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke asset_attributes
    public function assetAttributes()
    {
        return $this->hasMany(AssetAttribute::class, 'custom_attribute_id');
    }
}
