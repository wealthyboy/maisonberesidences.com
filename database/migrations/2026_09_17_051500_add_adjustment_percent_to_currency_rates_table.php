<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('currency_rates') && ! Schema::hasColumn('currency_rates', 'adjustment_percent')) {
            Schema::table('currency_rates', function (Blueprint $table) {
                $table->decimal('adjustment_percent', 8, 3)->default(0)->after('rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('currency_rates') && Schema::hasColumn('currency_rates', 'adjustment_percent')) {
            Schema::table('currency_rates', function (Blueprint $table) {
                $table->dropColumn('adjustment_percent');
            });
        }
    }
};
