<?php

/*
|--------------------------------------------------------------------------
| Panel de administración: Inicio
|--------------------------------------------------------------------------
|
| Se puede sobrescribir desde el .env de cPanel sin tocar código. Si la
| configuración está cacheada (php artisan config:cache), hay que volver a
| cachearla para que tome los cambios.
|
*/

return [

    // IP (o rangos) desde donde se puede consultar la base de pacientes en el atril, separadas por coma.
    // Vacío: sin restricción, cualquier equipo que abra el atril puede ver el nombre de quien tenga una cédula.
    'atril_ips' => env('ATRIL_IPS', ''),

    // Nombre de la unidad: se muestra bajo "Turnero HUV" en el menú lateral.
    'unidad_nombre' => env('PANEL_UNIDAD_NOMBRE', 'Unidad Básica de Atención'),

    // Nombre corto de la unidad.
    'unidad' => env('PANEL_UNIDAD', 'UBA'),

    // Umbrales del Inicio, en minutos. Pasado el umbral, la cifra se pinta en rojo.
    'umbrales' => [
        // Espera de un turno normal (pendiente).
        'espera' => (int) env('PANEL_UMBRAL_ESPERA', 30),
        // Espera de un turno prioritario (prioridad >= 4).
        'prioritario' => (int) env('PANEL_UMBRAL_PRIORITARIO', 15),
        // Duración de una atención abierta (turno llamado).
        'atencion' => (int) env('PANEL_UMBRAL_ATENCION', 20),
    ],

    // Soporte. Vacíos = no se usan.
    //  - correo: recibe cada solicitud nueva (necesita MAIL_* configurado en el .env; con MAIL_MAILER=log no sale).
    //  - contacto: se muestra en la página para urgencias, p. ej. "Extensión 1234 · innovacion@huv.gov.co".
    'soporte' => [
        'correo' => env('PANEL_SOPORTE_CORREO'),
        'contacto' => env('PANEL_SOPORTE_CONTACTO'),
    ],

];
