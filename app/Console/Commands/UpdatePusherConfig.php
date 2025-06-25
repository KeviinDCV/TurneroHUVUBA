<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Broadcasting\TurneroBroadcaster;
use Illuminate\Support\Facades\File;

class UpdatePusherConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'turnero:update-pusher-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza la configuración de Pusher para el sistema de turnero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Configurando sistema de tiempo real para el turnero...');

        // Asegurarnos que el archivo de configuración de broadcasting existe
        $broadcastingConfigPath = config_path('broadcasting.php');
        if (!File::exists($broadcastingConfigPath)) {
            $this->error('No se encontró el archivo de configuración de broadcasting');
            return 1;
        }

        // Inicializar el broadcaster personalizado
        TurneroBroadcaster::init();
        
        $this->info('Configuración de Pusher actualizada correctamente');
        $this->info('');
        $this->info('Nota: Para el funcionamiento correcto de las notificaciones en tiempo real:');
        $this->info('1. Asegúrese de tener una clave de Pusher en su archivo .env o use los valores predeterminados para desarrollo local.');
        $this->info('2. Agregue la siguiente configuración a su archivo .env:');
        $this->info('');
        $this->info('BROADCAST_DRIVER=pusher');
        $this->info('PUSHER_APP_ID=local_app_id');
        $this->info('PUSHER_APP_KEY=local_key');
        $this->info('PUSHER_APP_SECRET=local_secret');
        $this->info('PUSHER_HOST=127.0.0.1');
        $this->info('PUSHER_PORT=6001');
        $this->info('PUSHER_SCHEME=http');
        $this->info('PUSHER_APP_CLUSTER=mt1');
        $this->info('PUSHER_USE_TLS=false');

        return 0;
    }
}
