<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apartments')) {
            Schema::create('apartments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->nullable()->index();
                $table->decimal('price', 12, 2)->nullable();
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->date('sale_price_expires')->nullable();
                $table->string('image')->nullable();
                $table->unsignedInteger('quantity')->nullable();
                $table->unsignedInteger('max_adults')->nullable();
                $table->unsignedInteger('no_of_rooms')->nullable();
                $table->decimal('toilets', 4, 1)->nullable();
                $table->string('type')->nullable();
                $table->string('uuid')->nullable();
                $table->string('price_mode')->nullable();
                $table->unsignedBigInteger('apartment_id')->nullable();
                $table->string('video_link')->nullable();
                $table->text('image_link')->nullable();
                $table->boolean('allow')->default(true);
                $table->string('floor')->nullable();
                $table->text('teaser')->nullable();
                $table->string('owner_email')->nullable();
                $table->string('wifi_password')->nullable();
                $table->string('wifi_ssid')->nullable();
                $table->string('bedroom_1')->nullable();
                $table->string('bedroom_2')->nullable();
                $table->string('bedroom_3')->nullable();
                $table->string('bedroom_4')->nullable();
                $table->string('bedroom_5')->nullable();
                $table->string('bedroom_6')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('images')) {
            Schema::create('images', function (Blueprint $table) {
                $table->id();
                $table->string('image')->nullable();
                $table->string('caption')->nullable();
                $table->unsignedInteger('image_id')->nullable();
                $table->string('imageable_type')->nullable();
                $table->unsignedBigInteger('imageable_id')->nullable();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
        Schema::dropIfExists('apartments');
    }
};
