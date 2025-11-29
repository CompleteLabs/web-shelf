<?php

namespace App\Imports;

use App\Enums\AssetCondition;
use App\Enums\NbhStatus;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\Brand;
use App\Models\BusinessEntity;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private array $businessEntityCache = [];
    private array $categoryCache = [];
    private array $brandCache = [];
    private array $assetLocationCache = [];
    private array $userCache = [];

    public function __construct()
    {
        $this->preloadCaches();
    }

    private function preloadCaches(): void
    {
        $this->businessEntityCache = BusinessEntity::pluck('id', 'name')->toArray();
        $this->categoryCache = Category::pluck('id', 'name')->toArray();
        $this->brandCache = Brand::pluck('id', 'name')->toArray();
        $this->assetLocationCache = AssetLocation::pluck('id', 'name')->toArray();
        $this->userCache = User::pluck('id', 'name')->toArray();
    }

    public function collection(Collection $collection)
    {
        $assetsToInsert = [];
        $now = now();

        DB::beginTransaction();

        try {
            foreach ($collection as $row) {
                $purchaseDate = $this->parseDate($row['tanggal_pembelian']);
                $businessEntityId = $this->findOrCreateBusinessEntity($row['badan_usaha']);
                $categoryId = $this->findOrCreateCategory($row['kategori']);
                $brandId = $this->findOrCreateBrand($row['merek']);
                $assetLocationId = $this->findOrCreateAssetLocation($row['lokasi_aset']);
                $conditionStatus = $this->mapConditionStatus($row['status_aset']);
                $nbhStatus = $this->mapNbhStatus($row['status_nbh']);
                $nbhResponsibleId = $this->findUserByName($row['penanggung_jawab_nbh']);
                $recipientId = $this->findUserByName($row['penerima_aset']);
                $recipientBusinessEntityId = $this->findOrCreateBusinessEntity($row['badan_usaha_penerima']);
                $nbhReportedAt = $this->parseDate($row['tanggal_insiden']);

                $assetsToInsert[] = [
                    'purchase_date' => $purchaseDate,
                    'business_entity_id' => $businessEntityId,
                    'name' => $row['nama_aset'],
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'type' => $row['tipe'],
                    'serial_number' => $row['serial_number'],
                    'imei1' => $row['imei_1'],
                    'imei2' => $row['imei_2'],
                    'item_price' => $this->parsePrice($row['harga_aset']),
                    'qty' => $row['qty'] ?? 1,
                    'asset_location_id' => $assetLocationId,
                    'condition_status' => $conditionStatus,
                    'nbh_status' => $nbhStatus,
                    'nbh_responsible_user_id' => $nbhResponsibleId,
                    'nbh_reported_at' => $nbhReportedAt,
                    'recipient_id' => $recipientId,
                    'recipient_business_entity_id' => $recipientBusinessEntityId,
                    'is_available' => $conditionStatus === AssetCondition::Available->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($assetsToInsert)) {
                Asset::insert($assetsToInsert);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        unset($assetsToInsert);
        gc_collect_cycles();
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($value - 25569) * 86400))->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parsePrice($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $value);
        return (int) $cleaned ?: null;
    }

    private function findOrCreateBusinessEntity($name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        if (isset($this->businessEntityCache[$name])) {
            return $this->businessEntityCache[$name];
        }

        $entity = BusinessEntity::firstOrCreate(['name' => $name]);
        $this->businessEntityCache[$name] = $entity->id;

        return $entity->id;
    }

    private function findOrCreateCategory($name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        if (isset($this->categoryCache[$name])) {
            return $this->categoryCache[$name];
        }

        $entity = Category::firstOrCreate(['name' => $name]);
        $this->categoryCache[$name] = $entity->id;

        return $entity->id;
    }

    private function findOrCreateBrand($name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        if (isset($this->brandCache[$name])) {
            return $this->brandCache[$name];
        }

        $entity = Brand::firstOrCreate(['name' => $name]);
        $this->brandCache[$name] = $entity->id;

        return $entity->id;
    }

    private function findOrCreateAssetLocation($name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        if (isset($this->assetLocationCache[$name])) {
            return $this->assetLocationCache[$name];
        }

        $entity = AssetLocation::firstOrCreate(['name' => $name]);
        $this->assetLocationCache[$name] = $entity->id;

        return $entity->id;
    }

    private function findUserByName($name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        if (isset($this->userCache[$name])) {
            return $this->userCache[$name];
        }

        return null;
    }

    private function mapConditionStatus($status): string
    {
        if (empty($status)) {
            return AssetCondition::Available->value;
        }

        return match (strtolower(trim($status))) {
            'tersedia' => AssetCondition::Available->value,
            'digunakan' => AssetCondition::Transferred->value,
            'hilang' => AssetCondition::Lost->value,
            'rusak' => AssetCondition::Damaged->value,
            default => AssetCondition::Available->value,
        };
    }

    private function mapNbhStatus($status): string
    {
        if (empty($status)) {
            return NbhStatus::None->value;
        }

        return match (strtolower(trim($status))) {
            'tidak ada' => NbhStatus::None->value,
            'menunggu nbh' => NbhStatus::Pending->value,
            'nbh selesai' => NbhStatus::Resolved->value,
            default => NbhStatus::None->value,
        };
    }
}
