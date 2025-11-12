<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('showPrice')->default(true);
            $table->boolean('showRating')->default(true);
            $table->boolean('showLabels')->default(true);
            $table->integer('visibleCount')->default(5);
            $table->string('cardColor')->default('#ffffff');
            $table->string('textColor')->default('#000000');
            $table->string('starColor')->default('#FFD700');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
