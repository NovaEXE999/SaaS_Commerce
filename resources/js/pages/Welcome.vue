<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Scissors,
    Calendar,
    Clock,
    Phone,
    User,
    CheckCircle,
    Tv,
    Sparkles,
    Shield,
    Star,
    Layers
} from '@lucide/vue';

const props = defineProps<{
    barbershop?: {
        id: number;
        name: string;
        type: string;
    } | null;
    barbers?: Array<{
        id: number;
        name: string;
    }>;
    services?: Array<{
        id: number;
        name: string;
        description: string | null;
        price: string;
    }>;
}>();

const bookingSuccess = ref(false);

const form = useForm({
    business_id: props.barbershop?.id ?? 1,
    customer_name: '',
    customer_phone: '',
    user_id: '',
    service_id: '',
    start_time: '',
});

const submitBooking = () => {
    form.post('/public-booking', {
        preserveScroll: true,
        onSuccess: () => {
            bookingSuccess.value = true;
            form.reset('customer_name', 'customer_phone', 'user_id', 'service_id', 'start_time');
            setTimeout(() => {
                bookingSuccess.value = false;
            }, 6000);
        },
    });
};

const formatPrice = (value: string | number) => {
    const amount = typeof value === 'number' ? value : parseFloat(value ?? 0);
    return isNaN(amount) ? '$0' : `$${amount.toLocaleString('es-CO')}`;
};

const defaultServices = [
    {
        name: 'Corte Clásico & Degradado',
        price: '$25.000',
        duration: '40 min',
        description: 'Corte personalizado con tijera o máquina, lavado y acabado con productos premium.',
    },
    {
        name: 'Perfilado & Ritual de Barba',
        price: '$18.000',
        duration: '30 min',
        description: 'Toalla caliente, aceites esenciales, perfilado navaja libre y bálsamo hidratante.',
    },
    {
        name: 'Combo Completo Imperial',
        price: '$38.000',
        duration: '60 min',
        description: 'Corte de cabello completo + cuidado de barba + exfoliación facial relajante.',
    },
];

// Servicios reales del negocio si existen, si no los de demostración
const services = computed(() => {
    if (props.services && props.services.length > 0) {
        return props.services.map(s => ({
            id: s.id,
            name: s.name,
            price: formatPrice(s.price),
            duration: null as string | null,
            description: s.description || 'Servicio profesional de barbería.',
        }));
    }
    return defaultServices.map(s => ({ id: null as number | null, ...s }));
});
</script>

<template>
    <Head title="Bienvenido - Agendamiento Online" />

    <div class="min-h-screen bg-background text-foreground flex flex-col selection:bg-primary selection:text-primary-foreground">
        
        <!-- Navbar Superior -->
        <header class="border-b bg-card/60 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-lg bg-primary flex items-center justify-center text-primary-foreground font-bold shadow-md">
                        <Scissors class="h-5 w-5" />
                    </div>
                    <span class="font-bold text-lg tracking-tight">
                        {{ props.barbershop?.name ?? 'SAS Commerce Barber' }}
                    </span>
                    <Badge variant="secondary" class="hidden sm:inline-flex text-xs">
                        Agendamiento Oficial
                    </Badge>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/turnero">
                        <Button variant="outline" size="sm" class="gap-1.5 hidden md:flex">
                            <Tv class="h-4 w-4 text-blue-500" />
                            Turnero en Vivo
                        </Button>
                    </Link>

                    <template v-if="$page.props.auth?.user">
                        <Link :href="dashboard.url()">
                            <Button size="sm">Ir al Dashboard</Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login.url()">
                            <Button variant="ghost" size="sm">Iniciar Sesión</Button>
                        </Link>
                        <Link :href="register.url()">
                            <Button size="sm">Registrar Negocio</Button>
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden py-16 md:py-24 border-b bg-linear-to-b from-muted/50 to-background">
            <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-7 space-y-6">
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
                            Reserva en línea<span v-if="props.barbers?.length"> · {{ props.barbers.length }} {{ props.barbers.length === 1 ? 'profesional disponible' : 'profesionales disponibles' }}</span>
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                        Reserva tu corte en minutos sin esperar turno.
                    </h1>

                    <p class="text-lg text-muted-foreground leading-relaxed">
                        Elige tu barbero favorito, selecciona el horario que mejor se adapte a tu día y recibe recordatorios automáticos por WhatsApp.
                    </p>

                    <div class="grid grid-cols-3 gap-4 pt-4 border-t">
                        <div>
                            <div class="text-2xl font-bold text-primary">100%</div>
                            <div class="text-xs text-muted-foreground">Digital y Sincronizado</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">3 Avisos</div>
                            <div class="text-xs text-muted-foreground">1 día, 1 hora y 10 min</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">Puntos</div>
                            <div class="text-xs text-muted-foreground">Acumula con cada visita</div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Agendamiento Inmediato -->
                <div class="lg:col-span-5">
                    <Card class="shadow-xl border-primary/20 bg-card">
                        <CardHeader>
                            <CardTitle class="text-xl flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-primary" />
                                Agendar Cita Online
                            </CardTitle>
                            <CardDescription>
                                Completa tus datos para reservar tu turno de inmediato.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Alerta de Éxito -->
                            <div 
                                v-if="bookingSuccess" 
                                class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 dark:bg-green-950/50 dark:border-green-800 dark:text-green-300 flex items-start gap-3"
                            >
                                <CheckCircle class="h-5 w-5 text-green-600 shrink-0 mt-0.5" />
                                <div class="text-sm">
                                    <strong class="block font-semibold">¡Cita agendada exitosamente!</strong>
                                    Hemos guardado tu turno. El equipo te estará esperando a la hora seleccionada.
                                </div>
                            </div>

                            <form @submit.prevent="submitBooking" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="customer_name" class="flex items-center gap-1.5">
                                        <User class="h-3.5 w-3.5 text-muted-foreground" />
                                        Tu Nombre Completo
                                    </Label>
                                    <Input 
                                        id="customer_name" 
                                        v-model="form.customer_name" 
                                        required 
                                        placeholder="Ej. Andrés Morales" 
                                    />
                                    <span v-if="form.errors.customer_name" class="text-xs text-red-500">{{ form.errors.customer_name }}</span>
                                </div>

                                <div class="space-y-2">
                                    <Label for="customer_phone" class="flex items-center gap-1.5">
                                        <Phone class="h-3.5 w-3.5 text-muted-foreground" />
                                        Teléfono / WhatsApp
                                    </Label>
                                    <Input 
                                        id="customer_phone" 
                                        v-model="form.customer_phone" 
                                        required 
                                        placeholder="Ej. 3101234567" 
                                    />
                                    <span v-if="form.errors.customer_phone" class="text-xs text-red-500">{{ form.errors.customer_phone }}</span>
                                </div>

                                <div class="space-y-2">
                                    <Label for="user_id" class="flex items-center gap-1.5">
                                        <Scissors class="h-3.5 w-3.5 text-muted-foreground" />
                                        Selecciona tu Barbero
                                    </Label>                                    <Select v-model="form.user_id" required>
                                        <SelectTrigger id="user_id">
                                            <SelectValue placeholder="Elige un profesional" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem 
                                                v-for="barber in props.barbers" 
                                                :key="barber.id" 
                                                :value="barber.id.toString()"
                                            >
                                                {{ barber.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.user_id" class="text-xs text-red-500">{{ form.errors.user_id }}</span>
                                </div>

                                <div v-if="props.services && props.services.length > 0" class="space-y-2">
                                    <Label for="service_id" class="flex items-center gap-1.5">
                                        <Sparkles class="h-3.5 w-3.5 text-muted-foreground" />
                                        Selecciona tu Servicio
                                    </Label>
                                    <Select v-model="form.service_id">
                                        <SelectTrigger id="service_id">
                                            <SelectValue placeholder="Elige un servicio (opcional)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="service in props.services"
                                                :key="service.id"
                                                :value="service.id.toString()"
                                            >
                                                {{ service.name }} ({{ formatPrice(service.price) }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.service_id" class="text-xs text-red-500">{{ form.errors.service_id }}</span>
                                </div>

                                <div class="space-y-2">
                                    <Label for="start_time" class="flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                                        Fecha y Hora de la Cita
                                    </Label>
                                    <Input 
                                        id="start_time" 
                                        type="datetime-local" 
                                        v-model="form.start_time" 
                                        required 
                                    />
                                    <span v-if="form.errors.start_time" class="text-xs text-red-500">{{ form.errors.start_time }}</span>
                                </div>

                                <Button 
                                    type="submit" 
                                    class="w-full mt-2" 
                                    size="lg" 
                                    :disabled="form.processing"
                                >
                                    Confirmar y Agendar Cita
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </section>

        <!-- Sección de Servicios y Precios -->
        <section class="py-16 max-w-6xl mx-auto px-4 w-full">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <Badge variant="outline" class="mb-2">Nuestros Servicios</Badge>
                <h2 class="text-3xl font-bold tracking-tight">Estilo, Precisión y Cuidado</h2>
                <p class="text-muted-foreground mt-2">
                    Servicios profesionales con los mejores productos para el cuidado de tu cabello y barba.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card v-for="(service, idx) in services" :key="idx" class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-lg font-bold">{{ service.name }}</CardTitle>
                            <span class="text-xl font-extrabold text-primary">{{ service.price }}</span>
                        </div>
                        <CardDescription v-if="service.duration" class="flex items-center gap-1 pt-1">
                            <Clock class="h-3.5 w-3.5" />
                            Duración aproximada: {{ service.duration }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ service.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-8 mt-auto bg-muted/20">
            <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-muted-foreground">
                <div class="flex items-center gap-2">
                    <Layers class="h-4 w-4 text-primary" />
                    <span>SAS Commerce - Sistema Multi-tenant para Negocios Locales</span>
                </div>
                <div>
                    <span>© {{ new Date().getFullYear() }} SAS Commerce. Todos los derechos reservados.</span>
                </div>
            </div>
        </footer>

    </div>
</template>
