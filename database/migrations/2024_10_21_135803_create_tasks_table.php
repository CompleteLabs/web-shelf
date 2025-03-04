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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('business_entity_id')->constrained('business_entities');
            $table->string('name');
            $table->text('description');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->decimal('cost', 12, 2);
            $table->string('location');
            $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
