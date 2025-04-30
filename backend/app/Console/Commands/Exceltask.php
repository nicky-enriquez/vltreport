<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Carbon\Carbon;

class Exceltask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'excel-task';// {--processDate=}

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command que descarga de un archivo SFTP, lo procesa y lo envia aun correo electronico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '512M'); // Intenta con un valor mayor
        ini_set('max_execution_time', 120); // Establece el límite a 120 segundos

        \Log::info('Iniciando el proceso de archivo SFTP...');
        try 
        {
            $processDateString =null;// isset($this->option('processDate')) ?  $this->option('processDate'): null; // Obtiene la fecha del argumento de la línea de comandos

            if ($processDateString) {
                $processDate = Carbon::parse($processDateString);
            } else {
                $processDate = Carbon::now(); // Si no se proporciona, usa la fecha actual
            }

            $fechaNombreArchivo = $processDate->subDay()->format('Y-m-d');
            $año = $processDate->format('Y');
            $mesNumero = date('n');
            $months = env("CALENDAR_MONTHS");            
            $nombresMesesIngles = explode(',', $months);                        

            // Obtener el nombre del mes en inglés
            $nombreMesIngles = $nombresMesesIngles[$mesNumero-1];
            // Obtener la primera letra del nombre del mes en mayúscula
            $primeraLetraMesMayuscula = strtoupper(substr($nombreMesIngles, 0, 1));
            // Obtener el resto del nombre del mes en minúscula
            $restoNombreMesMinuscula = strtolower(substr($nombreMesIngles, 1));
            // Construir la parte {mes-año}
            $mesAñoFormato = $primeraLetraMesMayuscula . $restoNombreMesMinuscula . '_' . $año;
            
            // Configuración del SFTP (asegúrate de tener las credenciales en tu archivo .env)
            $sftpConfig = config('filesystems.disks.sftp');
            if (!$sftpConfig) {
                \Log::error('La configuración SFTP no se encontró.');
                $this->error('La configuración SFTP no se encontró.');
                return;
            }
        
            // Construir la ruta completa de la carpeta remota
            $rutaRemota = $año.'/'.$mesAñoFormato;        
            // Nombre del archivo en el SFTP 
            $nombreArchivoSFTP ="VLT_detailed_report_".$fechaNombreArchivo.".xlsx";//el proceso es de una dia antes, 'VLT_detailed_report_'.date('Y-m-d') .'.xlsx';
            $rutaArchivoSFTP = $rutaRemota.'/'.$nombreArchivoSFTP;
            //dd($rutaArchivoSFTP);
            // 1. Descargar el archivo desde el SFTP             
            if (Storage::disk('sftp')->exists($rutaArchivoSFTP)) {
                \Log::info("Descargando el archivo desde SFTP...");
                $content = Storage::disk('sftp')->get($rutaArchivoSFTP);
                Storage::disk('local')->put('temp/'.$nombreArchivoSFTP, $content);
                \Log::info("Archivo descargado exitosamente.");
            }
            else {
                \Log::error("El archivo {$nombreArchivoSFTP} no existe en el SFTP.");
                return;
            }

            // 2. Modificar el archivo Excel
            \Log::info('Modificando la primera hoja archivo Excel...');
            $rutaArchivoLocal = storage_path('app/private/temp/' . $nombreArchivoSFTP);
            $spreadsheet = IOFactory::load($rutaArchivoLocal);
            $sheetIndex = 0; // El índice 1 corresponde a la segunda hoja
            $sheet = $spreadsheet->getSheet($sheetIndex);
 
            // Ejemplo: Reemplazar la primera fila con nuevos datos
            $header = env("SPREAD_SHEET_TGA_HEADER");            
            $newHeader = explode(',', $header);            
            $sheet->fromArray($newHeader, null, env('SPREAD_SHEET_TGA_START_CELL')); // Escribe los datos a partir de la celda A1
            $rutaArchivoModificado = storage_path('app/private/temp/modified_'.$nombreArchivoSFTP);
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($rutaArchivoModificado);
            
            \Log::info('Modificando la segunda hoja archivo Excel...');
            $spreadsheet = IOFactory::load($rutaArchivoModificado);
            // Obtener la segunda hoja (los índices de las hojas comienzan en 0)
            $sheetIndex = 1; // El índice 1 corresponde a la segunda hoja
            $sheet = $spreadsheet->getSheet($sheetIndex);

            $header = env("SPREAD_SHEET_CASH_HEADER");
            $newHeader = explode(',', $header);            
            $sheet->fromArray($newHeader, null,env('SPREAD_SHEET_CASH_START_CELL')); // Escribe los datos a partir de la celda A1

            $rutaArchivoModificado = storage_path('app/private/temp/modified_'.$nombreArchivoSFTP);
            
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($rutaArchivoModificado);
            \Log::info("Archivo Excel modificado.");
 
            //  // 3. Enviar el archivo modificado por correo electrónico
            $correoDestinatario= env('MAIL_TO','nicky.enriquez@kurax.dev');
            \Log::info('Enviando el archivo por correo electrónico...');
            Mail::send('emails.tpl_modified_excel', [], function ($message) use ($rutaArchivoModificado, $correoDestinatario,$nombreArchivoSFTP) {
                 $message->to($correoDestinatario)
                         ->subject('Archivo Excel Modificado '.$nombreArchivoSFTP)
                         ->attach($rutaArchivoModificado);
            });
            \Log::info('Correo electrónico enviado.');
 
             // Eliminar los archivos temporales
             //Storage::disk('local')->delete('temp/' . $nombreArchivoSFTP);
             //Storage::disk('local')->delete('temp/archivo_modificado.xlsx');
             //$this->info('Archivos temporales eliminados.');
 
              \Log::info("Proceso completado.");

        } catch (\Exception $e) {
            \Log::error("Error al procesar el archivo SFTP: " . $e->getMessage());
        }
    }
}
