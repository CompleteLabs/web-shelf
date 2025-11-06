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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('condition_status')->default('available')->after('asset_location_id');
            $table->string('nbh_status')->default('none')->after('condition_status');
            $table->date('nbh_reported_at')->nullable()->after('nbh_status');
            $table->string('audit_document_path')->nullable()->after('image');
            $table->string('nbh_document_path')->nullable()->after('audit_document_path');
            $table->text('nbh_notes')->nullable()->after('nbh_document_path');
            $table->foreignId('nbh_responsible_user_id')->nullable()->after('recipient_business_entity_id')->constrained('users')->nullOnDelete();
        });

        DB::table('assets')
            ->where('is_available', true)
            ->update(['condition_status' => 'available']);

        DB::table('assets')
            ->where('is_available', false)
            ->update(['condition_status' => 'transferred']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nbh_responsible_user_id');
            $table->dropColumn([
                'condition_status',
                'nbh_status',
                'nbh_reported_at',
                'audit_document_path',
                'nbh_document_path',
                'nbh_notes',
            ]);
        });
    }
};
