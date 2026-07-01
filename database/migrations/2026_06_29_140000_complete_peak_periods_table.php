<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('peak_periods')) {
            Schema::create('peak_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->decimal('discount', 6, 2)->nullable();
                $table->decimal('increase_percent', 6, 2)->nullable();
                $table->integer('days_limit')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            return;
        }

        Schema::table('peak_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('peak_periods', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('peak_periods', 'discount')) {
                $table->decimal('discount', 6, 2)->nullable()->after('end_date');
            }

            if (! Schema::hasColumn('peak_periods', 'increase_percent')) {
                $table->decimal('increase_percent', 6, 2)->nullable()->after('discount');
            }

            if (! Schema::hasColumn('peak_periods', 'days_limit')) {
                $table->integer('days_limit')->nullable()->after('increase_percent');
            }

            if (! Schema::hasColumn('peak_periods', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('days_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('peak_periods', function (Blueprint $table) {
            foreach (['name', 'increase_percent', 'is_active'] as $column) {
                if (Schema::hasColumn('peak_periods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
