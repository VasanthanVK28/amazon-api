<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapeSchedule;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scrapeFrequency' => 'required|string|in:hourly,daily,weekly',
            'scrapeTime' => 'nullable|string',
            'scrapeDay' => 'nullable|string',
        ]);

        // ✅ Determine current day for daily/hourly tasks
        if ($validated['scrapeFrequency'] === 'daily' || $validated['scrapeFrequency'] === 'hourly') {
            $day = strtolower(Carbon::now()->format('D')); // 'mon','tue'...
        } else {
            $day = $validated['scrapeDay'] ?? strtolower(Carbon::now()->format('D'));
        }

        // ✅ Deactivate previous active schedules
        ScrapeSchedule::where('status', 'active')->update(['status' => 'inactive']);

        // ✅ Create new active schedule
        $schedule = ScrapeSchedule::create([
            'frequency' => $validated['scrapeFrequency'],
            'time' => $validated['scrapeTime'] ?? '03:00',
            'day' => $day,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => '✅ Scraping task scheduled successfully!',
            'data' => $schedule,
        ], 201);
    }

    public function index()
    {
        $latest = ScrapeSchedule::orderBy('created_at', 'desc')->first();

        if (!$latest) {
            return response()->json(['message' => 'No scraping schedule found'], 404);
        }

        return response()->json($latest);
    }
}
