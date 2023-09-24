<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->longText('description');
            $table->boolean('use_price_range')->default(true);
            $table->integer('price_min')->nullable();
            $table->integer('price_max')->nullable();
            $table->boolean('use_quantity')->default(true);
            $table->integer('quantity')->nullable();
            $table->longText('image_links')->nullable();
            $table->foreignUuid('user_id');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
