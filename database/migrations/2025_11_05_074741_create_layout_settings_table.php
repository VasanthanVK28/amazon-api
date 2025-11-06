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
        Schema::create('layout_settings', function (Blueprint $table) {
    $table->id();
    $table->boolean('show_price')->default(true);
    $table->boolean('show_rating')->default(true);
    $table->boolean('show_labels')->default(true);
    $table->integer('visible_count')->default(10);
    $table->string('card_color')->default('#ffffff');
    $table->string('text_color')->default('#1f2937');
    $table->string('star_color')->default('#facc15');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_settings');
    }
};
