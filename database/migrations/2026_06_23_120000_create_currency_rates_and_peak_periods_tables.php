<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currency_rates')) {
            Schema::create('currency_rates', function (Blueprint $table) {
                $table->id();
                $table->string('base_currency', 3)->default('USD');
                $table->string('quote_currency', 3)->default('NGN');
                $table->decimal('rate', 16, 6);
                $table->timestamp('retrieved_at')->nullable();
                $table->timestamps();
                $table->unique(['base_currency', 'quote_currency']);
            });
        }

        if (! Schema::hasTable('peak_periods')) {
            Schema::create('peak_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('increase_percent', 6, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'start_date', 'end_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('peak_periods');
        Schema::dropIfExists('currency_rates');
    }
};
