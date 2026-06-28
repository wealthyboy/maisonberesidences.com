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
        if (! Schema::hasTable('attributes')) {
            return;
        }

        Schema::table('attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('attributes', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('attributes', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('attributes', 'type')) {
                $table->string('type')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('attributes', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('slug');
            }

            if (! Schema::hasColumn('attributes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('attributes')) {
            return;
        }

        Schema::table('attributes', function (Blueprint $table) {
            if (Schema::hasColumn('attributes', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('attributes', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
