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
        Schema::create('vehicle_checksheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->onDelete('cascade');
            $table->string('reference_number')->unique(); // Unique Reference Number, e.g., KODE-2024-001
            $table->string('pic')->nullable(); // Person In Charge
            $table->string('license_plate'); // Vehicle License Plate
            $table->string('location')->nullable(); // Location
            $table->text('remarks')->nullable(); // Additional Remarks
            $table->integer('start_km')->nullable(); // Starting Kilometer
            $table->dateTime('departure_time')->nullable(); // Departure Time
            $table->text('departure_photo')->nullable(); // Photo at Departure
            $table->text('departure_damage_report')->nullable(); // Damage Report at Departure
            $table->integer('end_km')->nullable(); // Ending Kilometer
            $table->dateTime('return_time')->nullable(); // Return Time
            $table->text('return_photo')->nullable(); // Photo at Return
            $table->text('return_damage_report')->nullable(); // Damage Report at Return
            $table->float('rental_duration')->nullable(); // Rental Duration
            $table->float('distance_traveled')->default(0); // Distance Traveled
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_checksheets');
    }
};
