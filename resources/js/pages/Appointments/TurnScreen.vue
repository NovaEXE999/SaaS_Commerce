<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Activity } from '@lucide/vue';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    businessId: number;
    initialAppointments: any[];
}>();

const appointments = ref(props.initialAppointments);

onMounted(() => {
    // Escuchando los WebSockets a través de Laravel Echo y Reverb
    const echo = (window as any).Echo;
    if (echo) {
        echo.channel(`business.${props.businessId}.turns`)
            .listen('TurnUpdated', (e: any) => {
                console.log('Evento Recibido:', e.message);
                // Recargamos la data de Inertia de manera silenciosa
                router.reload({ only: ['initialAppointments'], onSuccess: (page) => {
                    appointments.value = page.props.initialAppointments as any[];
                }});
            });
    } else {
        console.warn('Laravel Echo no está configurado correctamente en window.');
    }
});

onUnmounted(() => {
    const echo = (window as any).Echo;
    if (echo) {
        echo.leaveChannel(`business.${props.businessId}.turns`);
    }
});
</script>

<template>
    <Head title="Turnero en Vivo" />
    
    <div class="min-h-screen bg-black text-white p-8 flex flex-col items-center justify-center">
        <h1 class="text-5xl font-bold mb-12 text-primary flex items-center gap-4">
            <Activity class="w-12 h-12 animate-pulse text-blue-500" />
            Turnos Actuales
        </h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-6xl">
            <Card v-for="(app, index) in appointments" :key="app.id" 
                  class="bg-zinc-900 border-zinc-800 text-white transform transition-all duration-500"
                  :class="{'scale-105 border-blue-500 shadow-[0_0_30px_rgba(59,130,246,0.5)]': index === 0}"
            >
                <CardContent class="p-10 flex flex-col items-center justify-center space-y-4">
                    <div class="text-2xl text-zinc-400">Barbero: {{ app.user.name }}</div>
                    <div class="text-6xl font-black text-white text-center">{{ app.customer.name }}</div>
                    <div class="text-3xl font-bold uppercase tracking-widest mt-4" 
                         :class="app.status === 'adelantada' ? 'text-orange-500 animate-bounce' : 'text-green-500'">
                        {{ app.status }}
                    </div>
                </CardContent>
            </Card>
            
            <div v-if="appointments.length === 0" class="col-span-full text-center text-4xl text-zinc-600 mt-20 font-bold">
                No hay turnos en espera por el momento.
            </div>
        </div>
    </div>
</template>
