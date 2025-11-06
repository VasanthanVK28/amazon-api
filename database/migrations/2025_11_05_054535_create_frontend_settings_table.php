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
        Schema::create('slider_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('show_price')->default(true);
            $table->boolean('show_rating')->default(true);
            $table->boolean('show_brand')->default(true);
            $table->integer('visible_items')->default(4);
            $table->json('color')->nullable(); // stores title, price, rating colors
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slider_settings');
    }
};
