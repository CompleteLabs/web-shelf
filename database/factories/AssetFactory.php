<?php

namespace Database\Factories;

use App\Models\AssetLocation;
use App\Models\Brand;
use App\Models\BusinessEntity;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brandsAndModels = [
            'FURNITURE' => [
                'Ikea' => ['MEJA', 'KURSI', 'LEMARI'],
                'Ashley' => ['SOFA', 'RAK-RAKAN', 'PAPAN TULIS']
            ],
            'PERKAKAS' => [
                'Bosch' => ['OBENG', 'TOOLBOX'],
                'Makita' => ['TALANG AC', 'TANGGA']
            ],
            'BARANG ELEKTRONIK' => [
                'Asus' => ['LAPTOP', 'HANDPHONE'],
                'Samsung' => ['KULKAS', 'MICROWAVE'],
                'Sony' => ['KAMERA', 'TV']
            ],
            'ACCESSORIES' => [
                'HP' => ['MOUSE', 'KEYBOARD'],
                'Logitech' => ['MIC WIRELESS', 'POINTER PRESENTASI']
            ]
        ];

        $category = $this->faker->randomElement(array_keys($brandsAndModels));
        $brand = $this->faker->randomElement(array_keys($brandsAndModels[$category]));
        $model = $this->faker->randomElement($brandsAndModels[$category][$brand]);

        return [
            'purchase_date' => $this->faker->date(),
            'business_entity_id' => BusinessEntity::inRandomOrder()->first()->id ?? BusinessEntity::factory(),
            'name' => "{$brand} {$model}",
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
            'brand_id' => Brand::inRandomOrder()->first()->id ?? Brand::factory(),
            'type' => $category,
            'serial_number' => strtoupper($this->faker->unique()->bothify('??#####')),
            'imei1' => $category === 'BARANG ELEKTRONIK' ? $this->faker->unique()->numerify('###############') : null,
            'imei2' => $category === 'BARANG ELEKTRONIK' ? $this->faker->unique()->numerify('###############') : null,
            'item_price' => $this->faker->numberBetween(1000, 10000),
            'asset_location_id' => AssetLocation::inRandomOrder()->first()->id ?? AssetLocation::factory(),
            'is_available' => $this->faker->boolean(),
        ];
    }
}
