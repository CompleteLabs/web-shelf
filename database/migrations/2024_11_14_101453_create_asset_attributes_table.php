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
        Schema::create('asset_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade'); // Relasi ke aset
            $table->foreignId('custom_attribute_id')->nullable()->constrained('custom_asset_attributes')->onDelete('set null'); // Relasi ke custom atribut
            $table->string('attribute_value')->nullable(); // Nilai dari atribut khusus, misalnya tanggal atau nilai lain
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_attributes');
    }
};
