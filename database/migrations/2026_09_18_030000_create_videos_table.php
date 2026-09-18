<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table): void {
            $table->id();
            $table->morphs('videoable');
            $table->string('filename')->nullable();
            $table->string('disk')->default('spaces');
            $table->string('path');
            $table->boolean('encoded')->default(false);
            $table->string('encoded_path')->nullable();
            $table->string('status')->default('uploaded')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
