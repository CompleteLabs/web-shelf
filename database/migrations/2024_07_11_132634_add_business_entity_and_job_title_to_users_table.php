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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('business_entity_id')->nullable()->after('email_verified_at');
            $table->foreign('business_entity_id')->references('id')->on('business_entities')->onDelete('set null');

            // Relasi job title
            $table->unsignedBigInteger('job_title_id')->nullable()->after('business_entity_id');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_entity_id']);
            $table->dropColumn('business_entity_id');

            $table->dropForeign(['job_title_id']);
            $table->dropColumn('job_title_id');
        });
    }
};
