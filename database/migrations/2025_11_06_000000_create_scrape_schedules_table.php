<?php
// database/migrations/2025_11_06_000000_create_scrape_schedules_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scrape_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('frequency', ['hourly', 'daily', 'weekly']);
            $table->string('time')->nullable(); // HH:MM for daily/weekly
            $table->string('day')->nullable();  // mon, tue, etc.
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_schedules');
    }
};
