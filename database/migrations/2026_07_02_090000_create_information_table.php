<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('information')) {
            return;
        }

        Schema::create('information', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('custom_link')->nullable();
            $table->string('teaser')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('blog')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information');
    }
};
