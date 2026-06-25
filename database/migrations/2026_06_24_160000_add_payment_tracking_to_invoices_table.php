<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // Some Maison Be databases pre-date the current invoice model.
            // Add the modern booking fields without disturbing legacy invoice data.
            if (! Schema::hasColumn('invoices', 'invoice')) {
                $table->string('invoice')->nullable()->unique();
            }

            if (! Schema::hasColumn('invoices', 'full_name')) {
                $table->string('full_name')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'payment_info')) {
                $table->text('payment_info')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'payment_status')) {
                $table->string('payment_status', 20)->default('paid');
            }

            if (! Schema::hasColumn('invoices', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->unique();
            }

            if (! Schema::hasColumn('invoices', 'payment_payload')) {
                $table->json('payment_payload')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $columns = collect(['paid_at', 'payment_payload', 'payment_reference', 'payment_status', 'description', 'payment_info', 'full_name', 'invoice'])
                ->filter(fn (string $column): bool => Schema::hasColumn('invoices', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
