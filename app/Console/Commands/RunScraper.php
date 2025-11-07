<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapeSchedule;
use Carbon\Carbon;

class RunScraper extends Command
{
    protected $signature = 'scraper:run';
    protected $description = 'Run scheduled scraping tasks';

    public function handle()
    {
        $now = Carbon::now();

        $schedules = ScrapeSchedule::where('status', 'active')->get();

        foreach ($schedules as $schedule) {

            // Skip if already running
            if ($schedule->is_running) continue;

            $run = false;

            // ---------------- Hourly ----------------
            if ($schedule->frequency === 'hourly') {
                if (!$schedule->last_run || $schedule->last_run->diffInMinutes($now) >= 60) {
                    $run = true;
                }
            }

            // ---------------- Daily ----------------
            elseif ($schedule->frequency === 'daily' && $schedule->time) {
                $schedTime = Carbon::parse($schedule->time);
                if (!$schedule->last_run || $schedule->last_run->format('Y-m-d H:i') != $now->format('Y-m-d H:i')) {
                    if ($now->between($schedTime, $schedTime->copy()->addMinute())) {
                        $run = true;
                    }
                }
            }

            // ---------------- Weekly ----------------
            elseif ($schedule->frequency === 'weekly' && $schedule->time && $schedule->day) {
                $schedTime = Carbon::parse($schedule->time);
                $currentDay = strtolower($now->format('D')); // thu, fri, etc.

                if ($currentDay === strtolower($schedule->day)) {
                    if (!$schedule->last_run || $schedule->last_run->format('Y-m-d H:i') != $now->format('Y-m-d H:i')) {
                        if ($now->between($schedTime, $schedTime->copy()->addMinute())) {
                            $run = true;
                        }
                    }
                }
            }

            // ---------------- Run scraper ----------------
            if ($run) {
                $this->info("✅ Running scraper for {$schedule->frequency} task...");

                $schedule->update([
                    'is_running' => true,
                    'status' => 'running',
                    'updated_at' => now(),
                ]);

                try {
                    // Run Python scraper
                    $output = shell_exec("python C:\\scraper_amazon\\main.py 2>&1");
                    $this->info($output);

                    // Mark complete and record last_run timestamp
                    $schedule->update([
                        'status' => 'complete',
                        'is_running' => false,
                        'last_run' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("✅ Scraper task '{$schedule->frequency}' completed successfully!");
                } catch (\Exception $e) {
                    $schedule->update([
                        'status' => 'failed',
                        'is_running' => false,
                        'updated_at' => now(),
                    ]);
                    $this->error("❌ Scraper failed: " . $e->getMessage());
                }
            }
        }
    }
}
