<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Log;

class TurneroBroadcaster
{
    /**
     * Inicializa la configuración de broadcasting
     */
    public static function init()
    {
        // Log para depuración
        Log::info('Inicializando TurneroBroadcaster');

        // Configurar programáticamente para usar Pusher con credenciales de la app
        app()->singleton('pusher', function ($app) {
            $config = $app['config']['broadcasting.connections.pusher'];
            
            return new \Pusher\Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options'] ?? []
            );
        });
    }

    /**
     * Envía un mensaje a un canal específico utilizando Pusher
     * 
     * @param string $channel Nombre del canal
     * @param string $event Nombre del evento
     * @param array $data Datos a enviar
     * @return bool
     */
    public static function broadcast($channel, $event, $data)
    {
        try {
            app('pusher')->trigger($channel, $event, $data);
            Log::info("Mensaje enviado a canal: {$channel}, evento: {$event}", ['data' => $data]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error al enviar mensaje: " . $e->getMessage(), [
                'canal' => $channel,
                'evento' => $event,
                'datos' => $data
            ]);
            return false;
        }
    }

    /**
     * Envía notificación específica de turno llamado
     * 
     * @param \App\Models\Turno $turno
     * @return bool
     */
    public static function notificarTurnoLlamado($turno)
    {
        // Refrescar el modelo para asegurar que tenga los datos más recientes
        $turno->refresh();

        // Cargar relaciones necesarias si no están cargadas
        if (!$turno->relationLoaded('caja')) {
            $turno->load('caja');
        }
        
        if (!$turno->relationLoaded('servicio')) {
            $turno->load('servicio');
        }

        // Verificar que el turno tenga fecha_llamado (debe estar en estado 'llamado')
        if (!$turno->fecha_llamado) {
            Log::error('Intento de notificar turno sin fecha_llamado', [
                'turno_id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'estado' => $turno->estado
            ]);
            return false;
        }

        // Datos para la transmisión
        $datos = [
            'id' => $turno->id,
            'codigo_completo' => $turno->codigo_completo,
            'caja' => $turno->caja ? $turno->caja->nombre : null,
            'numero_caja' => $turno->caja ? $turno->caja->numero_caja : null,
            'servicio' => $turno->servicio ? $turno->servicio->nombre : null,
            'fecha_llamado' => $turno->fecha_llamado->format('Y-m-d H:i:s'),
            'timestamp' => now()->timestamp // Agregar timestamp para forzar actualización en el cliente
        ];

        Log::info('Enviando notificación de turno llamado al televisor', [
            'turno_id' => $turno->id,
            'codigo_completo' => $turno->codigo_completo,
            'fecha_llamado' => $datos['fecha_llamado'],
            'timestamp' => $datos['timestamp']
        ]);

        return self::broadcast('turnero', 'turno.llamado', $datos);
    }
} 