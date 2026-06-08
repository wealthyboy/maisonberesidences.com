<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table) {
                if (! Schema::hasColumn('properties', 'city')) {
                    $table->string('city')->nullable()->after('address');
                }

                if (! Schema::hasColumn('properties', 'state')) {
                    $table->string('state')->nullable()->after('city');
                }

                if (! Schema::hasColumn('properties', 'country')) {
                    $table->string('country')->nullable()->after('state');
                }

                if (! Schema::hasColumn('properties', 'sale_price')) {
                    $table->decimal('sale_price', 12, 2)->nullable()->after('price');
                }

                if (! Schema::hasColumn('properties', 'bathrooms')) {
                    $table->unsignedSmallInteger('bathrooms')->nullable()->after('bedrooms');
                }

                if (! Schema::hasColumn('properties', 'max_guests')) {
                    $table->unsignedSmallInteger('max_guests')->nullable()->after('bathrooms');
                }

                if (! Schema::hasColumn('properties', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)->after('description');
                }

                if (! Schema::hasColumn('properties', 'is_available')) {
                    $table->boolean('is_available')->default(true)->after('is_featured');
                }
            });

            return;
        }

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->nullable();
            $table->string('status')->default('draft');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('max_guests')->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            foreach (['city', 'state', 'country', 'sale_price', 'bathrooms', 'max_guests', 'is_featured', 'is_available'] as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
