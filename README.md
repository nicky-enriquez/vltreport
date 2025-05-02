# vltreport

   Modulo que descarga desde SFTP un excel ,lo modifica y envia a correo 

## Tecnologias utilizadas
   - [Laravel 12](https://laravel.com/)
   - PHP >= 8.3 (dentro del contenedor)
   - Docker / Docker Compose
   - Composer (dentro del contenedor)

## Requisitos

   Antes de comenzar, asegúrate de tener instaladas las siguientes herramientas:

   - [Docker](https://www.docker.com/)
   - [Docker Compose](https://docs.docker.com/compose/)

## Instalación

1. Clona el repositorio:

   ```bash

   git clone https://github.com/nicky-enriquez/vltreport.git
   cd vltreport

2. Copia el archivo de entorno:
   
   ```bash
   
   cp .env.example .env

3. Configura las variables del entorno.
   
   ```bash

   #Configuracion para el sftp
   SFTP_HOST=dominiosftp
   SFTP_USERNAME=tuusuariosftp
   SFTP_PASSWORD=tuclavesftp
   SFTP_PORT=tupuerto 
   SFTP_PROCESS_TIME=09:30
   
   #Configuracion para el excel
   SPREAD_SHEET_TGA_HEADER="columna1,columa 2,columna 4" #columnas de reemplazo en el hoja 1 del excel descargado
   SPREAD_SHEET_TGA_START_CELL="A1" #celda de inicio para reemplazar las cabeceras de la hoja 1 del excel descargado
   SPREAD_SHEET_CASH_HEADER="otracolumna1,otra columa 2,columna 4" #columnas de reemplazo en el hoja 2 del excel descargado
   SPREAD_SHEET_CASH_START_CELL="A1" #celda de inicio para reemplazar las cabeceras de la hoja 2 del excel descargado

   #Configuracion para el correo
   MAIL_MAILER=smtp
   MAIL_SCHEME=null
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=465
   MAIL_USERNAME=tucorreo@gmail.com
   MAIL_PASSWORD=tuclave
   MAIL_FROM_ADDRESS="tucorreo@gmail.com"
   MAIL_FROM_NAME="${APP_NAME}"
   MAIL_TO="destinatario1@gmail.com,destinatario2@gmail.com"
   MAIL_CC="correocopia@dominio.com" #opcional
   MAIL_TO_ERROR="nicky.enriquez@kurax.dev" #correo para recibir errores

   #Configuracion para el php
   PHP_MEMORY_LIMIT=512M
   PHP_MAX_EXECUTION_TIME=120
   
4. Levanta los contenedores:   
   ```bash   
   docker-compose up -d --build

5. Ejecuta el programador tareas automaticas schedule artisan(cron) desde docker :   
   ```bash
   docker exec vltreport sh -c "php artisan schedule:work >> storage/logs/cron_laravel.log 2>&1 &"

7. Puedes revisar los pasos de la tarea en el log laravel.log
   ```bash
   cat backend/storage/logs/laravel.log

8. Puedes revisar los pasos que artisan schedule escribe en el log cron_laravel.log   
   ```bash
   cat backend/storage/logs/cron_laravel.log

9. En caso cambies en produccion la hora de proceso tienes que ejecutar tambien este comando 
   ```bash
   docker exec vltreport sh -c "php artisan config:clear"
[Nota]
   En caso no exista el documento llegara un correo de aviso a destinatario.
   En caso se genere un error llegara al correo de soporte de TI

10. En caso desee probarlo por comando con fecha proceso actual
   ```bash
   php artisan excel-task 

11. En caso desee probarlo por comando con fecha proceso elegido
   ```bash
   php artisan excel-task processDate=2025-04-30   

12. En caso no lea las variables el env. 
   ```bash
   php artisan config:clear