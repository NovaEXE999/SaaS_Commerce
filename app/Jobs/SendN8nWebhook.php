<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar de forma asíncrona los Webhooks a n8n.
 *
 * **Cómo usarlo**
 * 1. Configura la variable de entorno en tu ``.env``:
 *    ``N8N_WEBHOOK_URL="https://tu-n8n.com/webhook/endpoint"``
 * 2. En el controlador (por ejemplo, ``AppointmentController``) llama:
 *    ``\App\Jobs\SendN8nWebhook::dispatch('nombre_evento', $payload);``
 * 3. El job leerá la URL del ``.env`` y enviará un POST JSON con:
 *    ```json
 *    {
 *      "event": "nombre_evento",
 *      "data": { "key": "value" },
 *      "timestamp": "2024-01-01T12:00:00Z"
 *    }
 *    ```
 * 4. En desarrollo, si la URL sigue el placeholder ``tu-dominio.com``
 *    solo se hará un ``Log::info`` simulando el envío.
 *
 * Esta implementación permite que puedas cambiar *únicamente* la URL en ``.env``
 * cuando crees tu flujo en n8n, sin tocar código.
 */
class SendN8nWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string Evento que dispara el webhook */
    public $event;

    /** @var array Datos que se enviarán */
    public $payload;

    /**
     * Crear una nueva instancia del job.
     *
     * @param  string  $event  Nombre descriptivo del evento (ej: 'appointment_created')
     * @param  array  $payload  Información estructurada que será enviada a n8n
     */
    public function __construct(string $event, array $payload)
    {
        $this->event = $event;
        $this->payload = $payload;
    }

    /**
     * Ejecutar el job.
     * Se ejecuta en segundo plano para no bloquear la petición HTTP del usuario.
     */
    public function handle(): void
    {
        // ---------------------------------------------------------------------
        // DOCUMENTACIÓN PARA N8N:
        // 1️⃣ Añade en ``.env`` la variable ``N8N_WEBHOOK_URL`` con la URL del webhook.
        // 2️⃣ n8n recibirá un POST con la siguiente estructura JSON:
        //    {
        //        "event": "<nombre_evento>",
        //        "data":  { <tu_payload> },
        //        "timestamp": "<ISO8601>"
        //    }
        // ---------------------------------------------------------------------
        $webhookUrl = env('N8N_WEBHOOK_URL', 'https://n8n.tu-dominio.com/webhook/test-url');

        // Si la URL sigue apuntando al placeholder, solo simulamos con un log.
        if (str_contains($webhookUrl, 'tu-dominio.com')) {
            Log::info("[SendN8nWebhook] Simulación de envío a n8n (evento: {$this->event})", $this->payload);

            return;
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'event' => $this->event,
                'data' => $this->payload,
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::info("[SendN8nWebhook] Webhook enviado exitosamente a n8n (evento: {$this->event})");
        } catch (\Exception $e) {
            Log::error("[SendN8nWebhook] Error enviando webhook a n8n (evento: {$this->event}): ".$e->getMessage());
        }
    }
}
