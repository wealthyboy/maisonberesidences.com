<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartment_date_blocks', function (Blueprint $table): void {
            $table->string('source', 24)->default('manual')->after('reason')->index();
            $table->string('external_id', 160)->nullable()->after('source');
            $table->string('external_type', 80)->nullable()->after('external_id');
            $table->json('external_payload')->nullable()->after('external_type');
            $table->timestamp('synced_at')->nullable()->after('external_payload');

            $table->unique(['source', 'external_id'], 'date_blocks_source_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('apartment_date_blocks', function (Blueprint $table): void {
            $table->dropUnique('date_blocks_source_external_unique');
            $table->dropIndex(['source']);
            $table->dropColumn([
                'source',
                'external_id',
                'external_type',
                'external_payload',
                'synced_at',
            ]);
        });
    }
};
