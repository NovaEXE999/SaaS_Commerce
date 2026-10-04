<?php

namespace App\Console\Commands;

use App\Jobs\SendN8nWebhook;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Comando de consola para disparar recordatorios programados de citas a n8n.
 *
 * Debe ser ejecutado cada minuto por el scheduler de Laravel.
 * Dispara webhooks para citas que estén a:
 * - 1 día (24 horas)
 * - 1 hora
 * - 10 minutos
 *
 * Uso manual: php artisan appointments:notify-reminders
 */
class NotifyAppointmentReminders extends Command
{
    protected $signature = 'appointments:notify-reminders';

    protected $description = 'Dispara webhooks a n8n para citas próximas (1 día, 1 hora, 10 min antes).';

    public function handle(): int
    {
        $now = Carbon::now();

        // Ventana de tolerancia de 1 minuto para cada umbral
        $thresholds = [
            [
                'label' => '1_day_reminder',
                'from' => $now->copy()->addHours(23)->addMinutes(59),
                'to' => $now->copy()->addHours(24)->addMinutes(1),
            ],
            [
                'label' => '1_hour_reminder',
                'from' => $now->copy()->addMinutes(59),
                'to' => $now->copy()->addMinutes(61),
            ],
            [
                'label' => '10_min_reminder',
                'from' => $now->copy()->addMinutes(9),
                'to' => $now->copy()->addMinutes(11),
            ],
        ];

        $totalDispatched = 0;

        foreach ($thresholds as $threshold) {
            $appointments = Appointment::with(['customer', 'user', 'business'])
                ->whereIn('status', ['agendada', 'adelantada'])
                ->whereBetween('start_time', [$threshold['from'], $threshold['to']])
                ->get();

            foreach ($appointments as $appointment) {
                if (! $appointment->customer || ! $appointment->user) {
                    continue;
                }

                SendN8nWebhook::dispatch($threshold['label'], [
                    'appointment_id' => $appointment->id,
                    'customer_name' => $appointment->customer->name,
                    'customer_phone' => $appointment->customer->phone ?? 'N/A',
                    'start_time' => $appointment->start_time->toIso8601String(),
                    'barber_name' => $appointment->user->name,
                    'business_id' => $appointment->business_id,
                    'business_name' => $appointment->business->name ?? '',
                    'reminder_type' => $threshold['label'],
                ]);

                $totalDispatched++;
            }

            if ($appointments->isNotEmpty()) {
                $this->info("[{$threshold['label']}] {$appointments->count()} recordatorio(s) enviado(s).");
            }
        }

        if ($totalDispatched === 0) {
            $this->info('No hay citas próximas que requieran recordatorios en este momento.');
        } else {
            $this->info("Total: {$totalDispatched} recordatorio(s) despachado(s) a n8n.");
        }

        return self::SUCCESS;
    }
}
