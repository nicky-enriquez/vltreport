<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Http\Request;

class ProcessExcelController extends Controller
{
    //
    function process(Request $request)
    {
        $processDate = $request->input('proccessDate'); // Obtén el valor del parámetro del request

        Artisan::call('app:process-file-sftp', [
            '--processDate' => $processDate,
        ]);

        return response()->json(['message' => 'Proceso de Excel iniciado con fecha: ' . $processDate]);
    }
}
