<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\NbhStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'condition_status',
        'nbh_status',
        'nbh_reported_at',
        'audit_document_path',
        'nbh_document_path',
        'nbh_notes',
        'nbh_responsible_user_id',
        'qty',
        'is_available',
        'recipient_id',
        'recipient_business_entity_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'condition_status' => AssetCondition::class,
        'nbh_status' => NbhStatus::class,
        'nbh_reported_at' => 'date',
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(AssetAttribute::class);
    }

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

    public function nbhResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nbh_responsible_user_id');
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

    public function scopeSortByItemAge(Builder $query, string $direction = 'asc')
    {
        $query->orderByRaw('DATEDIFF(NOW(), purchase_date) ' . $direction);
    }

    public function getIsAvailableAttribute($value)
    {
        if ($this->condition_status instanceof AssetCondition) {
            return $this->condition_status->label();
        }

        return $value ? 'Tersedia' : 'Transfer';
    }

    public function setIsAvailableAttribute($value): void
    {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolValue === null) {
            $this->attributes['is_available'] = $value;

            return;
        }

        $this->attributes['is_available'] = $boolValue;
        $this->attributes['condition_status'] = $boolValue
            ? AssetCondition::Available->value
            : AssetCondition::Transferred->value;
    }

    public function getConditionStatusLabelAttribute(): string
    {
        return $this->condition_status instanceof AssetCondition
            ? $this->condition_status->label()
            : 'Tidak Diketahui';
    }

    public function getConditionStatusColorAttribute(): string
    {
        return $this->condition_status instanceof AssetCondition
            ? $this->condition_status->color()
            : 'secondary';
    }

    public function setConditionStatusAttribute($value): void
    {
        if ($value instanceof AssetCondition) {
            $enum = $value;
        } else {
            $enum = AssetCondition::tryFrom((string) $value) ?? AssetCondition::Available;
        }

        $this->attributes['condition_status'] = $enum->value;
        $this->attributes['is_available'] = $enum === AssetCondition::Available;

        if (in_array($enum, [AssetCondition::Lost, AssetCondition::Damaged], true)) {
            if (($this->attributes['nbh_status'] ?? null) === NbhStatus::None->value || !isset($this->attributes['nbh_status'])) {
                $this->setNbhStatusAttribute(NbhStatus::Pending);
            }
        } else {
            $this->setNbhStatusAttribute(NbhStatus::None);
        }
    }

    public function getNbhStatusLabelAttribute(): string
    {
        return $this->nbh_status instanceof NbhStatus
            ? $this->nbh_status->label()
            : NbhStatus::None->label();
    }

    public function getNbhStatusColorAttribute(): string
    {
        return $this->nbh_status instanceof NbhStatus
            ? $this->nbh_status->color()
            : NbhStatus::None->color();
    }

    public function setNbhStatusAttribute($value): void
    {
        if ($value instanceof NbhStatus) {
            $enum = $value;
        } else {
            $enum = NbhStatus::tryFrom((string) $value) ?? NbhStatus::None;
        }

        $this->attributes['nbh_status'] = $enum->value;

        if ($enum === NbhStatus::None) {
            $this->attributes['nbh_responsible_user_id'] = null;
            $this->attributes['nbh_reported_at'] = null;
            $this->attributes['audit_document_path'] = null;
            $this->attributes['nbh_document_path'] = null;
            $this->attributes['nbh_notes'] = null;
        }
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

        if (in_array($this->condition_status, [AssetCondition::Lost, AssetCondition::Damaged], true)) {
            if ($this->nbh_status === NbhStatus::None) {
                return false;
            }

            if ($this->nbh_status === NbhStatus::Resolved) {
                return !empty($this->audit_document_path)
                    && !empty($this->nbh_responsible_user_id);
            }

            return true;
        }

        // Jika recipient memiliki role 'general_affair', status aset harus available
        if ($hasGeneralAffairRole && $this->condition_status !== AssetCondition::Available) {
            return false;
        }

        // Jika recipient tidak memiliki role 'general_affair', status aset harus transferred
        if (!$hasGeneralAffairRole && $this->condition_status !== AssetCondition::Transferred) {
            return false;
        }

        // Jika semua pengecekan valid, kembalikan true
        return true;
    }

    public function vehicleChecksheets(): HasMany
    {
        return $this->hasMany(VehicleChecksheet::class, 'asset_id');
    }
}
