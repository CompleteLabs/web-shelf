<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date')->nullable();
            $table->foreignId('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('imei1')->nullable();
            $table->string('imei2')->nullable();
            $table->bigInteger('item_price')->nullable();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->boolean('is_available')->default(true);
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
