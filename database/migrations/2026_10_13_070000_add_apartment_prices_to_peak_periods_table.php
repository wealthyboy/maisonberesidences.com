<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peak_periods', function (Blueprint $table) {
            $table->json('apartment_prices')->nullable();
            $table->string('price_currency', 3)->default('USD');
        });
    }

    public function down(): void
    {
        Schema::table('peak_periods', function (Blueprint $table) {
            $table->dropColumn(['apartment_prices', 'price_currency']);
        });
    }
};
