<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoClient;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Http\Controllers\Controller;

class ScraperController extends Controller
{
    // ✅ Declare the property
    private static $process = null;
     private $mongoClient;
    private $db;

    public function __construct()
    {
        $this->mongoClient = new MongoClient("mongodb://localhost:27017");
        $this->db = $this->mongoClient->amazon_scraper;
    }


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

public function addScrapeRequest(Request $request)
{
    $category = trim($request->input('query'));
    if (!$category) {
        return response()->json(['status' => 'error', 'message' => 'Category is required'], 400);
    }

    $collection = $this->db->scrape_requests;

    // Insert a new request regardless of existing entries
    $collection->insertOne([
        'category' => $category,
        'scraped' => false,
        'requested_at' => new \MongoDB\BSON\UTCDateTime()
    ]);

    Log::info("📥 Category queued for scraping (manual/add): {$category}");

    return response()->json([
        'status' => 'ok',
        'message' => "Category '{$category}' queued for scraping"
    ]);
}

    /**
     * Optional: Trigger a manual scrape immediately (not recommended if using scheduler)
     */
    public function scrapeNow(Request $request)
    {
        $category = trim($request->input('query', 'mobile'));
        $pages = (int) $request->input('pages', 5);

        $python = 'C:\\Users\\vasan\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';
        $script = 'C:\\scraper_amazon\\amazon.py';

        // Escape paths and args
        $python = escapeshellarg($python);
        $script = escapeshellarg($script);
        $categoryArg = escapeshellarg($category);
        $pagesArg = escapeshellarg($pages);

        // Run asynchronously in background
        $command = "$python $script $categoryArg $pagesArg > NUL 2>&1 &";
        exec($command);

        Log::info("Started manual scraping for category: {$category}, pages: {$pages}");

        return response()->json([
            'status' => 'ok',
            'message' => "Scraping started for category: {$category}"
        ]);
    }

    
}
