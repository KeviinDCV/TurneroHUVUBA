<?php

namespace App\Events;

use App\Models\Turno;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TurnoLlamado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $turno;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Turno $turno)
    {
        $this->turno = $turno;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('turnero');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'turno.llamado';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->turno->id,
            'codigo_completo' => $this->turno->codigo_completo,
            'caja' => $this->turno->caja ? $this->turno->caja->nombre : null,
            'numero_caja' => $this->turno->caja ? $this->turno->caja->numero_caja : null,
            'servicio' => $this->turno->servicio ? $this->turno->servicio->nombre : null,
            'fecha_llamado' => $this->turno->fecha_llamado->format('Y-m-d H:i:s')
        ];
    }
} 