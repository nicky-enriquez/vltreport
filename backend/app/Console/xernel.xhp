<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
     /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, string>
     */
    protected $commands = [
        \App\Console\Commands\Exceltask::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        //$processTime = env('SFTP_PROCESS_TIME', '14:02'); // Lee la hora desde .env, con un valor por defecto si no está definido

        $schedule->command('app:excel-task')->timezone('America/Lima');
                 //->dailyAt($processTime)
                 //->timezone('America/Lima'); // Especifica la zona horaria (opcional pero recomendado)
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
