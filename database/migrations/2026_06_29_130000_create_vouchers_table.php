<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (! Schema::hasColumn('vouchers', 'limits')) {
                    $table->unsignedInteger('limits')->nullable()->after('expires');
                }

                if (! Schema::hasColumn('vouchers', 'used_count')) {
                    $table->unsignedInteger('used_count')->default(0)->after('limits');
                }
            });

            return;
        }

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('from_value', 12, 2)->nullable();
            $table->string('type')->default('general');
            $table->boolean('status')->default(true);
            $table->boolean('valid')->default(true);
            $table->timestamp('expires')->nullable();
            $table->unsignedInteger('limits')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
