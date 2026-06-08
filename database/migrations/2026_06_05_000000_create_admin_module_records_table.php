<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_module_records', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug')->index();
            $table->string('title');
            $table->string('status')->default('draft')->index();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_module_records');
    }
};
