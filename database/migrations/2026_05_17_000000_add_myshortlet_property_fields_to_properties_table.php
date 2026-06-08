<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'mode')) {
                $table->string('mode')->nullable()->after('type');
            }

            if (! Schema::hasColumn('properties', 'price_mode')) {
                $table->string('price_mode')->nullable()->after('price');
            }

            if (! Schema::hasColumn('properties', 'size')) {
                $table->string('size')->nullable()->after('sale_price');
            }

            if (! Schema::hasColumn('properties', 'toilets')) {
                $table->decimal('toilets', 4, 1)->nullable()->after('bedrooms');
            }

            if (! Schema::hasColumn('properties', 'allow')) {
                $table->boolean('allow')->default(true)->after('description');
            }

            if (! Schema::hasColumn('properties', 'featured')) {
                $table->boolean('featured')->default(false)->after('allow');
            }

            if (! Schema::hasColumn('properties', 'is_refundable')) {
                $table->boolean('is_refundable')->default(false)->after('featured');
            }

            if (! Schema::hasColumn('properties', 'cancellation_message')) {
                $table->text('cancellation_message')->nullable()->after('is_refundable');
            }

            if (! Schema::hasColumn('properties', 'cancellation_fee')) {
                $table->decimal('cancellation_fee', 12, 2)->nullable()->after('cancellation_message');
            }

            if (! Schema::hasColumn('properties', 'virtual_tour')) {
                $table->string('virtual_tour')->nullable()->after('cancellation_fee');
            }

            if (! Schema::hasColumn('properties', 'is_price_negotiable')) {
                $table->boolean('is_price_negotiable')->default(false)->after('virtual_tour');
            }

            if (! Schema::hasColumn('properties', 'is_shortlet')) {
                $table->boolean('is_shortlet')->default(true)->after('is_price_negotiable');
            }

            if (! Schema::hasColumn('properties', 'location_full_name')) {
                $table->string('location_full_name')->nullable()->after('country');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            foreach ([
                'mode',
                'price_mode',
                'size',
                'toilets',
                'allow',
                'featured',
                'is_refundable',
                'cancellation_message',
                'cancellation_fee',
                'virtual_tour',
                'is_price_negotiable',
                'is_shortlet',
                'location_full_name',
            ] as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
