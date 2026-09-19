<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_services', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_usd', 12, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('available_for_all_apartments')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('apartment_additional_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('additional_service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['apartment_id', 'additional_service_id'], 'apartment_service_unique');
        });

        Schema::create('invoice_additional_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('additional_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->decimal('unit_price_usd', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_additional_services');
        Schema::dropIfExists('apartment_additional_service');
        Schema::dropIfExists('additional_services');
    }
};
