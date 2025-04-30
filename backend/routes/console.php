<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\Exceltask;


Schedule::command(Exceltask::class)
    ->dailyAt(env("SFTP_PROCESS_TIME"))
    ->timezone("America/Lima");