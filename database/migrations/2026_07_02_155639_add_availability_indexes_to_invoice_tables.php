<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            if (! $this->indexExists('invoice_items', 'invoice_items_availability_lookup_index')) {
                $table->index(['apartment_id', 'checkin', 'checkout'], 'invoice_items_availability_lookup_index');
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if (! $this->indexExists('invoices', 'invoices_payment_status_index')) {
                $table->index('payment_status', 'invoices_payment_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            if ($this->indexExists('invoice_items', 'invoice_items_availability_lookup_index')) {
                $table->dropIndex('invoice_items_availability_lookup_index');
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if ($this->indexExists('invoices', 'invoices_payment_status_index')) {
                $table->dropIndex('invoices_payment_status_index');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
