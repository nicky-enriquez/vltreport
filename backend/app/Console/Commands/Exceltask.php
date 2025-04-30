<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Carbon\Carbon;
use App\Helpers\LogTailHelper;

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

    protected $logTail;
    
    public function handle()
    {
        ini_set('memory_limit', env('PHP_MEMORY_LIMIT'));
        ini_set('max_execution_time',env('PHP_EXECUTION_TIME')); 
        
        $this->logTail = new LogTailHelper();
        $this->logTail->markPosition();

        \Log::info('Iniciando el Proceso desde SFTP...');
        try 
        {
            $processDateString ="2025-04-30";// isset($this->option('processDate')) ?  $this->option('processDate'): null; // Obtiene la fecha del argumento de la línea de comandos

            [$pathFile,$fileName]=$this->makePath($processDateString);

            //1. Descargar el archivo desde el SFTP
            $isDownloaded=$this->downloadExcel($pathFile,$fileName);

            if($isDownloaded) {
                // 2. Modificar el archivo Excel            
                $rutaArchivoModificado = $this->modifyExcel($fileName);
                // 3. Enviar el archivo modificado por correo electrónico
                $this->sendEmail($rutaArchivoModificado, $fileName, true);
            
                // 4. Eliminar los archivos temporales
                //$this->trash();
                \Log::info("Proceso exitoso.");
            }
            else {
                $this->sendEmail("", $fileName, false);
            }                                      

        } catch (\Exception $e) {
            $errorDetails = [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea'   => $e->getLine(),
                'traza'   => $e->getTraceAsString(), // opcional, toda la traza
            ];
            $contenido = "
            <strong>Error:</strong> {$errorDetails['mensaje']}<br>
            <strong>Archivo:</strong> {$errorDetails['archivo']}<br>
            <strong>Línea:</strong> {$errorDetails['linea']}<br>
            <br><strong>Traza:</strong><br><pre>{$errorDetails['traza']}</pre>";

            \Log::error("Error al procesar el archivo SFTP : " . $contenido);

            $this->sendEmailError($fileName);
        }
    }

    private function makePath($processDateString){

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
        
        // Construir la ruta completa de la carpeta remota
        $rutaRemota = $año.'/'.$mesAñoFormato;        
        // Nombre del archivo en el SFTP 
        $nombreArchivoSFTP ="VLT_detailed_report_".$fechaNombreArchivo.".xlsx";//el proceso es de una dia antes, 'VLT_detailed_report_'.date('Y-m-d') .'.xlsx';
        $rutaArchivoSFTP = $rutaRemota.'/'.$nombreArchivoSFTP;

        return [$rutaArchivoSFTP,$nombreArchivoSFTP];
    }

    private function downloadExcel($rutaArchivoSFTP,$nombreArchivoSFTP) : bool {
        // Configuración del SFTP (asegúrate de tener las credenciales en tu archivo .env)
        $sftpConfig = config('filesystems.disks.sftp');
        if (!$sftpConfig) {
            \Log::error('La configuración SFTP no se encontró.');
            return false;
        }

        // 1. Descargar el archivo desde el SFTP             
        if (Storage::disk('sftp')->exists($rutaArchivoSFTP)) {
            \Log::info("Descargando el archivo excel desde SFTP...");
            $content = Storage::disk('sftp')->get($rutaArchivoSFTP);
            Storage::disk('local')->put('temp/'.'_'.$nombreArchivoSFTP, $content);
            \Log::info("Archivo descargado exitosamente.");
            return true;
        }
        else {
            \Log::error("El archivo {$nombreArchivoSFTP} no existe en el SFTP.");
            return false;
        }
    }

    
    private function modifyExcel($nombreArchivoSFTP) {
        \Log::info('Modificando la primera hoja archivo Excel...');
        $rutaArchivoLocal = storage_path('app/private/temp/'.'_'.$nombreArchivoSFTP);
        $spreadsheet = IOFactory::load($rutaArchivoLocal);
        $sheetIndex = 0;
        $sheet = $spreadsheet->getSheet($sheetIndex);        
        $header = env("SPREAD_SHEET_TGA_HEADER");            
        $newHeader = explode(',', $header);            
        $sheet->fromArray($newHeader, null, env('SPREAD_SHEET_TGA_START_CELL')); // Escribe los datos a partir de la celda A1
        $rutaArchivoModificado = storage_path('app/private/temp/'.$nombreArchivoSFTP);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($rutaArchivoModificado);

        \Log::info('Modificando la segunda hoja archivo Excel...');
        $spreadsheet = IOFactory::load($rutaArchivoModificado);        
        $sheetIndex = 1;
        $sheet = $spreadsheet->getSheet($sheetIndex);
        $header = env("SPREAD_SHEET_CASH_HEADER");
        $newHeader = explode(',', $header);            
        $sheet->fromArray($newHeader, null,env('SPREAD_SHEET_CASH_START_CELL')); // Escribe los datos a partir de la celda A1
        $rutaArchivoModificado = storage_path('app/private/temp/'.$nombreArchivoSFTP);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($rutaArchivoModificado);
        
        \Log::info("Archivo Excel modificado.");

        return $rutaArchivoModificado;
    }

    private function sendEmail($rutaArchivoModificado, $nombreArchivoSFTP,$existeExcel) : void {
        // Lista de destinatarios principales
        $mailTo=env('MAIL_TO');
        $correosDestinatarios = explode(',', $mailTo);
        $mailCc=env('MAIL_CC');
        $correosCC = explode(',', $mailCc);

        \Log::info('Enviando el archivo por correo electrónico...');
        Mail::send('emails.tpl_modified_excel', ["existeExcel"=>$existeExcel], function ($message) use ($rutaArchivoModificado, $correosDestinatarios,$correosCC,$nombreArchivoSFTP,$existeExcel) {
            if(count($correosDestinatarios)>0) {
                $message->to($correosDestinatarios);
            }
            else {
                \Log::error('No hay destinatarios asignados. por favor revisar');        
                throw new Exception("No hay destinatarios asignados. por favor revisar");
            }
                

            if(count($correosCC)>0)
                $message->cc($correosCC);        
        
            $message ->subject('Presentación en Excel de Reporte Detallado TGA y CASH ('.$nombreArchivoSFTP.')');
            
            if($existeExcel)
                $message->attach($rutaArchivoModificado);
        });
        \Log::info('Correo electrónico enviado.');
    }

    private function trash($nombreArchivoSFTP)
    {
        // Eliminar los archivos temporales
        //Storage::disk('local')->delete('temp/_'.$nombreArchivoSFTP);        
        $this->info('Archivos temporales eliminados.');
    }

    private function sendEmailError($nombreArchivoSFTP) : void {
        // Lista de destinatarios principales
        $mailTo=env('MAIL_TO_ERROR');
        $mailTo = explode(',', $mailTo);
        
        $newLogContent = $this->logTail->getNewLogLines();        
        \Log::info('Enviando el log error por correo electrónico...');
        Mail::send('emails.tpl_error', ["error"=>$newLogContent], function ($message) use ($mailTo,$nombreArchivoSFTP) {            
                $message->to($mailTo)
                ->subject('Error en Proceso de Excel de Reporte Detallado TGA y CASH ('.$nombreArchivoSFTP.')');
                
        });
        \Log::info('Correo electrónico enviado.');
    }
}
