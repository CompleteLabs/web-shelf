<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\BusinessEntity;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class AssetImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row)
        {
            // Skip the header row
            if ($row[0] == 'JENIS BARANG') {
                continue;
            }

            // Lookup or create the category, brand, and business entity
            $category = Category::firstOrCreate(['name' => $row[0]]);
            $brand = Brand::firstOrCreate(['name' => $row[1]]);
            $businessEntity = BusinessEntity::firstOrCreate(['name' => $row[8]]);

            // Insert data into the assets table
            \DB::table('assets')->insert([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'business_entity_id' => $businessEntity->id,
                'type' => $row[2],
                'name' => $row[3],
                'serial_number' => $row[4],
                'imei1' => $row[5],
                'imei2' => $row[6],
                'item_price' => $row[7],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
