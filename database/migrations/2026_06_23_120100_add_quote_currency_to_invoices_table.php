<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'currency_code')) {
                $table->string('currency_code', 3)->default('USD')->after('currency');
            }

            if (! Schema::hasColumn('invoices', 'exchange_rate')) {
                $table->decimal('exchange_rate', 16, 6)->default(1)->after('currency_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }

            if (Schema::hasColumn('invoices', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
