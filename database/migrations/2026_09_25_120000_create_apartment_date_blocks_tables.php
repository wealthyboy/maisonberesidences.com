<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartment_date_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->default('Manual block');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('apartment_date_block', function (Blueprint $table): void {
            $table->foreignId('apartment_date_block_id')
                ->constrained('apartment_date_blocks')
                ->cascadeOnDelete();
            $table->foreignId('apartment_id')
                ->constrained('apartments')
                ->cascadeOnDelete();

            $table->primary(['apartment_date_block_id', 'apartment_id']);
            $table->index(['apartment_id', 'apartment_date_block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_date_block');
        Schema::dropIfExists('apartment_date_blocks');
    }
};
