<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_entity_id');
            $table->string('letter_number')->unique(); // Nomor Dokumen BAST
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->timestamp('transfer_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('document')->nullable();

            $table->foreign('business_entity_id')->references('id')->on('business_entities')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};
