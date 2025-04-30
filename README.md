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

   cp .env.example .env

3. Configura las variables del entorno.
   
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

4. Levanta los contenedores:
   docker-compose up -d --build

5. Ejecutar el programador tareas automaticas (cron) desde docker :
   docker exec vltreport sh -c "php artisan schedule:work >> storage/logs/cron_laravel.log 2>&1 &"

7. Puedes revisar los pasos de la tarea en el log laravel.log
   cat backend/storage/logs/laravel.log

8. Puedes revisar los pasos que artisan schedule tarea en el log cron_laravel.log   
   cat backend/storage/logs/cron_laravel.log
