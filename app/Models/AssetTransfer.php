<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_entity_id',
        'letter_number',
        'from_user_id',
        'to_user_id',
        'document',
        'transfer_date',
    ];

    // Relasi ke tabel business_entities
    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    // Relasi ke tabel assets
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Relasi ke tabel asset_transfer_details
    public function details(): HasMany
    {
        return $this->hasMany(AssetTransferDetail::class);
    }

    // Relasi ke tabel users untuk from_user_id
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    // Relasi ke tabel users untuk to_user_id
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function scopeGeneralAffair($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'general_affair');
        });
    }

    // Metode untuk menghitung status
    public function getStatusAttribute()
    {
        $this->loadMissing('fromUser.roles', 'toUser.roles');

        $fromUser = $this->fromUser;
        $toUser = $this->toUser;

        if ($fromUser && $fromUser->hasRole('general_affair') && !$toUser->hasRole('general_affair')) {
            return 'BERITA ACARA SERAH TERIMA';
        }

        if ($fromUser && !$fromUser->hasRole('general_affair') && !$toUser->hasRole('general_affair')) {
            return 'BERITA ACARA PENGALIHAN BARANG';
        }

        if ($fromUser && !$fromUser->hasRole('general_affair') && $toUser->hasRole('general_affair')) {
            return 'BERITA ACARA PENGEMBALIAN BARANG';
        }

        return 'Unknown Status';
    }
}
