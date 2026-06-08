<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice')->unique();
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('country')->nullable();
                $table->string('currency', 12)->default('₦');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->string('discount_type')->default('fixed');
                $table->decimal('caution_fee', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->text('payment_info')->nullable();
                $table->text('description')->nullable();
                $table->boolean('sent')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->date('checkin')->nullable();
                $table->date('checkout')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
