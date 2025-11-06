<?php
// app/Console/Kernel.php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScrapeSchedule;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedules = ScrapeSchedule::where('status', 'active')->get();

        foreach ($schedules as $task) {
            $pythonPath = env('PYTHON_PATH', 'C:\\scraper_amazon\\.venv\\Scripts\\python.exe');
            $scriptPath = 'C:\\scraper_amazon\\scheduler.py';
            $command = "\"$pythonPath\" \"$scriptPath\"";

            switch ($task->frequency) {
                case 'hourly':
                    $schedule->exec($command)->hourly();
                    break;

                case 'daily':
                    $schedule->exec($command)->dailyAt($task->time ?? '03:00');
                    break;

                case 'weekly':
                    $schedule->exec($command)->weeklyOn(
                        $this->mapDayToNumber($task->day ?? 'sun'),
                        $task->time ?? '03:00'
                    );
                    break;
            }
        }
    }

    private function mapDayToNumber($day)
    {
        $days = [
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3,
            'thu' => 4, 'fri' => 5, 'sat' => 6,
        ];
        return $days[strtolower($day)] ?? 0;
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
