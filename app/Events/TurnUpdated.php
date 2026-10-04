<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TurnUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $businessId;
    public $appointment;

    /**
     * Crea un nuevo evento para avisar a los clientes en la barbería.
     */
    public function __construct($businessId, $appointment = null)
    {
        $this->businessId = $businessId;
        $this->appointment = $appointment;
    }

    /**
     * Obtener el canal en el que se debe transmitir el evento.
     */
    public function broadcastOn(): array
    {
        // Emitimos al canal público de esta barbería específica (multi-tenant)
        return [
            new Channel('business.' . $this->businessId . '.turns'),
        ];
    }
    
    /**
     * Datos específicos que viajarán por WebSocket
     */
    public function broadcastWith(): array
    {
        return [
            'appointment' => $this->appointment,
            'message' => '¡Atención! Hay una actualización en los turnos.',
        ];
    }
}
