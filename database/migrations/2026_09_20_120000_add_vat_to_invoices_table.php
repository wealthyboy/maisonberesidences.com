<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('vat_rate', 5, 2)->default(0)->after('discount');
            $table->decimal('vat_amount', 14, 2)->default(0)->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['vat_rate', 'vat_amount']);
        });
    }
};
