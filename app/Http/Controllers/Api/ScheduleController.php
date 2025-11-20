<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapeSchedule;
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
}
