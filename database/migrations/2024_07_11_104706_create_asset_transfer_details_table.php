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
        Schema::create('asset_transfer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_transfer_id'); // Ubah dari transfer_id ke asset_transfer_id
            $table->unsignedBigInteger('asset_id');
            $table->string('equipment')->nullable();

            $table->foreign('asset_transfer_id')->references('id')->on('asset_transfers')->onDelete('cascade'); // Ubah foreign key
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transfer_details');
    }
};
