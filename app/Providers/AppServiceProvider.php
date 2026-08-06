<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ===================================================================
        // NOTA: aquí había un bloque que reescribía en CADA petición
        // `app.url` y la configuración de sesión a partir de la request.
        // Se eliminó porque hacía daño y no aportaba nada:
        //
        // 1) `config(['app.url' => ...])` era REDUNDANTE. Laravel ya genera
        //    las URLs (`url()`, `asset()`, `route()`) con el esquema y host de
        //    la petición actual; `app.url` solo se usa por consola/colas.
        //    Comprobado: con `app.url` apuntando a otro dominio, una petición
        //    http:// generaba URLs http:// y una https:// las generaba https://.
        //    Es decir, el HTTPS ya es NATIVO: no hace falta forzarlo.
        //
        // 2) `session.secure => request()->isSecure()` SÍ rompía cosas: pisaba
        //    el valor de SESSION_SECURE_COOKIE del .env y hacía que la cookie
        //    llevara el flag `Secure` solo en las peticiones HTTPS. Como el
        //    turnero se usa por las dos vías (el kiosco del TV entra por HTTP
        //    y el panel por HTTPS), quien iniciaba sesión por HTTPS perdía la
        //    sesión al pasar por HTTP: el navegador ya no manda esa cookie.
        //
        // Ahora manda `config/session.php`, que lee del .env
        // (SESSION_SECURE_COOKIE, SESSION_SAME_SITE, SESSION_DOMAIN...), que es
        // el comportamiento estándar de Laravel y un solo sitio donde mirar.
        // ===================================================================

        // Inicializar el broadcaster personalizado
        \App\Broadcasting\TurneroBroadcaster::init();

        // Otras configuraciones...
    }
}
