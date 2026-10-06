<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope')->default('global')->index();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('discount_type')->default('percent');
            $table->decimal('discount_value', 12, 2);
            $table->string('promo_text', 160)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['scope', 'apartment_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
