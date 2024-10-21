<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_date',
        'business_entity_id',
        'name',
        'image',
        'category_id',
        'brand_id',
        'type',
        'serial_number',
        'imei1',
        'imei2',
        'item_price',
        'asset_location_id',
        'qty',
        'is_available',
        'recipient_id',
        'recipient_business_entity_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',  // Casting 'is_available' sebagai boolean
    ];

    // Relasi ke tabel business_entities
    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    // Relasi ke tabel categories
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke tabel brands
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Relasi ke tabel asset_locations
    public function assetLocation()
    {
        return $this->belongsTo(AssetLocation::class);
    }

    // Relasi ke tabel asset_transfers
    public function assetTransfers()
    {
        return $this->hasMany(AssetTransfer::class);
    }

    // Relasi ke tabel asset_transfer_details
    public function assetTransferDetails()
    {
        return $this->hasMany(AssetTransferDetail::class);
    }

    // Relasi ke tabel users untuk recipient_id
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Relasi ke tabel business_entities untuk recipient_business_entity_id
    public function recipientBusinessEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'recipient_business_entity_id');
    }

    private function formatDiff($value, $unit)
    {
        return $value . ' ' . $unit;
    }

    public function getItemAgeAttribute()
    {
        $purchaseDate = Carbon::parse($this->attributes['purchase_date']);
        $now = Carbon::now();

        $diffInDays = $purchaseDate->diffInDays($now);
        $diffInMonths = $purchaseDate->diffInMonths($now);
        $diffInYears = $purchaseDate->diffInYears($now);

        if ($diffInYears > 0) {
            return $this->formatDiff($diffInYears, 'tahun');
        } elseif ($diffInMonths > 0) {
            return $this->formatDiff($diffInMonths, 'bulan');
        } else {
            return $this->formatDiff($diffInDays, 'hari');
        }
    }

    public function getIsAvailableAttribute($value)
    {
        return $value ? 'Tersedia' : 'Transfer';
    }

    public function checkValidRecipient()
    {
        // Ambil transfer terbaru terkait dengan asset ini dari tabel asset_transfer_details
        $latestTransferDetail = AssetTransferDetail::where('asset_id', $this->id)
            ->latest()
            ->first();

        // Jika tidak ada transfer detail, anggap valid (karena tidak ada data untuk dibandingkan)
        if (!$latestTransferDetail) {
            return true;
        }

        // Ambil transfer terkait dari tabel asset_transfers
        $latestTransfer = AssetTransfer::find($latestTransferDetail->asset_transfer_id);

        // Jika tidak ada transfer terkait, anggap valid
        if (!$latestTransfer) {
            return true;
        }

        // Cek apakah recipient_id di assets sama dengan to_user_id di asset_transfers
        if ($this->recipient_id != $latestTransfer->to_user_id) {
            return false;
        }

        // Ambil user recipient berdasarkan recipient_id
        $recipient = User::find($this->recipient_id);

        // Jika recipient tidak ditemukan, anggap tidak valid
        if (!$recipient) {
            return false;
        }

        // Cek apakah recipient memiliki role 'general_affair'
        $hasGeneralAffairRole = $recipient->hasRole('general_affair'); // Asumsi ada metode hasRole()

        // Jika recipient memiliki role 'general_affair', is_available harus 1
        if ($hasGeneralAffairRole && $this->is_available != 'Tersedia') {
            return false;
        }

        // Jika recipient tidak memiliki role 'general_affair', is_available harus 0
        if (!$hasGeneralAffairRole && $this->is_available != 'Transfer') {
            return false;
        }

        // Jika semua pengecekan valid, kembalikan true
        return true;
    }
}
