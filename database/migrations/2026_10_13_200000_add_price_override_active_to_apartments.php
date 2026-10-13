<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', fn (Blueprint $table) => $table->boolean('price_override_active')->default(false));
    }

    public function down(): void
    {
        Schema::table('apartments', fn (Blueprint $table) => $table->dropColumn('price_override_active'));
    }
};
