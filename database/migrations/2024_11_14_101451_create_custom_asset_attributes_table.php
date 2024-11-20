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
        Schema::create('custom_asset_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama atribut, misalnya 'Batas Pajak Tahunan'
            $table->string('type'); // Tipe input, misalnya 'TextInput'
            $table->boolean('required')->default(false); // Apakah atribut wajib diisi
            $table->boolean('is_active')->default(true); // Status aktif atau tidak
            $table->json('category_id')->nullable();
            $table->boolean('is_notifiable')->default(false); // Apakah akan menghasilkan notifikasi
            $table->enum('notification_type', ['fixed_date', 'relative_date', 'monthly'])->nullable(); // Jenis notifikasi
            $table->integer('notification_offset')->nullable(); // Offset dalam hari untuk relative_date
            $table->date('fixed_notification_date')->nullable(); // Tanggal tetap untuk fixed_date
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_asset_attributes');
    }
};
