<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_identity_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_reference', 100)->unique();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('document_disk');
            $table->string('document_path');
            $table->string('document_mime', 100);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_identity_documents');
    }
};
