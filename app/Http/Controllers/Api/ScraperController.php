<?php

namespace App\Http\Controllers\Api; // ✅ Must match folder structure

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Http\Controllers\Controller;

class ScraperController extends Controller
{
    public function scrape(Request $request)
    {
        $query = $request->input('query', 'mobile');
        $pages = (int) $request->input('pages', 3);

        // Full path to your python executable and project main.py
        $python = 'C:\\Users\\vasan\\AppData\\Local\\Programs\\Python\\Python314\\python.exe'; // on Windows: C:\\path\\to\\venv\\Scripts\\python.exe
        $script = 'C:\\scraper_amazon\\main.py';

        // Build command: pass args (safer than concatenating one string)
        $process = new Process([$python, $script, $query, (string)$pages]);
        // optionally set working directory and env
        $process->setTimeout(300); // adjust
        $process->setIdleTimeout(60);

        try {
            $process->mustRun();

            $output = $process->getOutput();
            // If your Python prints or returns JSON you can decode it:
            // $data = json_decode($output, true);

            return response()->json([
                'status' => 'ok',
                'message' => 'Scraping started/completed',
                'output' => $output,
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
}
