<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapeSchedule;
use App\Models\ScrapeLog;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        // Return schedules sorted by created date (latest first)
        $schedules = ScrapeSchedule::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $schedules]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scrapeFrequency' => 'required|string|in:hourly,daily,weekly',
            'scrapeTime' => 'nullable|string',
            'scrapeDay' => 'nullable|string',
            'categories' => 'required|array',
        ]);

        $unique = ['frequency' => $validated['scrapeFrequency']];

        if ($validated['scrapeFrequency'] === 'daily') {
            $unique['time'] = $validated['scrapeTime'];
        }

        if ($validated['scrapeFrequency'] === 'weekly') {
            $unique['time'] = $validated['scrapeTime'];
            $unique['day'] = $validated['scrapeDay'];
        }

        $schedule = ScrapeSchedule::updateOrCreate(
            $unique,
            [
                'categories' => $validated['categories'],
                'status' => 'active',
                'last_run' => null,
                'is_running' => false,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => '✅ Scraping task scheduled successfully!',
            'data' => $schedule
        ]);
    }

    public function runScrape($id)
{
    $schedule = ScrapeSchedule::find($id);

    if (!$schedule) {
        return response()->json(['error' => 'Schedule not found'], 404);
    }

    // Mark schedule as running
    $schedule->is_running = true;
    $schedule->save();

    $start = now();

    // ---- Run your scraper here ----
    // e.g., call Python script, or internal scraping logic
    // scrapeCategories($schedule->categories);

    $end = now();

    // Update schedule
    $schedule->last_run = $end;
    $schedule->is_running = false;
    $schedule->status = "complete";
    $schedule->save();

    // Create a log entry
    ScrapeLog::create([
        'schedule_id' => $schedule->_id,
        'frequency'   => $schedule->frequency,
        'categories'  => $schedule->categories,
        'start_time'  => $start,
        'end_time'    => $end,
        'status'      => 'completed',
    ]);

    return response()->json([
        'message' => 'Scrape completed and logged successfully',
    ]);
}

public function logs()
{
    $logs = ScrapeLog::orderBy('created_at', 'desc')->get();
    return response()->json(['data' => $logs]);
}


}
