<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Http\Controllers\Controller;

class ScraperController extends Controller
{
    // ✅ Declare the property
    private static $process = null;

    public function scrape(Request $request)
    {
        $query = $request->input('query', 'mobile');
        $pages = (int)$request->input('pages', 3);

        $python = 'C:\\Users\\vasan\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';
        $script = 'C:\\scraper_amazon\\main.py';

        $process = new Process([$python, $script, $query, (string)$pages]);
        $process->setTimeout(300);
        $process->setIdleTimeout(60);

        try {
            $process->mustRun();

            return response()->json([
                'status' => 'ok',
                'message' => 'Scraping finished',
                'output' => $process->getOutput(),
            ]);
        } catch (ProcessFailedException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Scraper failed',
                'error' => $e->getMessage(),
                'process_output' => $process->getErrorOutput(),
            ], 500);
        }
    }

public function scraper(Request $request) 
{
    // Get category and pages from request
    $category = $request->input('query', 'mobile');
    $pages = (int) $request->input('pages', 5);

    // Python executable & script path
    $python = 'C:\\Users\\vasan\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';
    $script = 'C:\\scraper_amazon\\amazon.py';

    // Ensure paths are escaped properly
    $python = escapeshellarg($python);
    $script = escapeshellarg($script);
    $categoryArg = escapeshellarg($category);
    $pagesArg = escapeshellarg($pages);

    // Command to run asynchronously in background
    $command = "$python $script $categoryArg $pagesArg > NUL 2>&1 &";

    // Execute the command
    exec($command);

    // Log the action
    Log::info("Started scraping for category: {$category}, pages: {$pages}");

    // Return response immediately
    return response()->json([
        'status' => 'ok',
        'message' => "Scraping started for category: {$category}"
    ]);
}

}
