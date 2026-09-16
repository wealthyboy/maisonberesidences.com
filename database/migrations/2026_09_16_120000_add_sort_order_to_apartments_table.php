<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('apartments', 'sort_order')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->index();
            });
        }

        DB::table('apartments')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->each(function ($id, $index): void {
                DB::table('apartments')
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('apartments', 'sort_order')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
