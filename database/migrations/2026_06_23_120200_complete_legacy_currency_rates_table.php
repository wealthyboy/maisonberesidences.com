<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('currency_rates', 'base_currency')) {
                $table->string('base_currency', 3)->default('USD')->after('id');
            }

            if (! Schema::hasColumn('currency_rates', 'quote_currency')) {
                $table->string('quote_currency', 3)->default('NGN')->after('base_currency');
            }

            if (! Schema::hasColumn('currency_rates', 'retrieved_at')) {
                $table->timestamp('retrieved_at')->nullable()->after('rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            if (Schema::hasColumn('currency_rates', 'retrieved_at')) {
                $table->dropColumn('retrieved_at');
            }

            if (Schema::hasColumn('currency_rates', 'quote_currency')) {
                $table->dropColumn('quote_currency');
            }

            if (Schema::hasColumn('currency_rates', 'base_currency')) {
                $table->dropColumn('base_currency');
            }
        });
    }
};
