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
            Schema::create('attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('attributes')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->nullable()->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('apartment_attribute')) {
            Schema::create('apartment_attribute', function (Blueprint $table) {
                $table->id();
                $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['apartment_id', 'attribute_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_attribute');
        Schema::dropIfExists('attributes');
    }
};
