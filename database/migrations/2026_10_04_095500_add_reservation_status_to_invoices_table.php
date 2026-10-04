<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'reservation_status')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->string('reservation_status', 20)
                    ->default('confirmed')
                    ->after('payment_status')
                    ->index();
            });
        }

        if (! Schema::hasColumn('invoices', 'canceled_at')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->timestamp('canceled_at')->nullable()->after('paid_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'canceled_at')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('canceled_at');
            });
        }

        if (Schema::hasColumn('invoices', 'reservation_status')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('reservation_status');
            });
        }
    }
};
