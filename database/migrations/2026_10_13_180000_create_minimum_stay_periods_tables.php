<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minimum_stay_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('minimum_nights');
            $table->boolean('applies_to_all')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'starts_on', 'ends_on']);
        });
        Schema::create('apartment_minimum_stay_period', function (Blueprint $table): void {
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('minimum_stay_period_id')->constrained()->cascadeOnDelete();
            $table->primary(['apartment_id', 'minimum_stay_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_minimum_stay_period');
        Schema::dropIfExists('minimum_stay_periods');
    }
};
