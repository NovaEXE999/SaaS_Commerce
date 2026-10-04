<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Layers,
    Store,
    NotebookPen,
    Scissors,
    ShoppingBag,
    PackageCheck,
    Gift,
    BellRing,
    CalendarCheck,
    TrendingUp,
    ShieldCheck,
    Tv,
    Wallet,
    ArrowRight,
    Check,
} from '@lucide/vue';

const props = defineProps<{
    stats: {
        businesses: number;
        appointments: number;
        points: number;
    };
}>();

const features = [
    {
        icon: ShoppingBag,
        title: 'Punto de Venta (POS)',
        description: 'Registra ventas en segundos con control de stock en tiempo real y comprobantes de pago por transferencia.',
    },
    {
        icon: PackageCheck,
        title: 'Inventario con alertas',
        description: 'Stock mínimo configurable por producto y alertas automáticas en el dashboard antes de quedarte sin existencias.',
    },
    {
        icon: Gift,
        title: 'Fidelización por puntos',
        description: 'Tus clientes acumulan 1 punto por cada $5.000 y los canjean por descuentos. Vuelven solos.',
    },
    {
        icon: CalendarCheck,
        title: 'Agenda de barbería',
        description: 'Citas con auditoría justificada, adelantos aprobados por el cliente y catálogo de servicios con precio.',
    },
    {
        icon: BellRing,
        title: 'Recordatorios automáticos',
        description: 'Webhooks hacia n8n que disparan avisos por WhatsApp 1 día, 1 hora y 10 minutos antes de cada cita.',
    },
    {
        icon: TrendingUp,
        title: 'Dashboard financiero',
        description: 'Métricas de ventas de hoy vs. ayer y gráfica de 7 días con desglose por tipo de negocio.',
    },
    {
        icon: Tv,
        title: 'Turnero en tiempo real',
        description: 'Pantalla para TV sincronizada por WebSockets con la agenda de tu barbería.',
    },
    {
        icon: ShieldCheck,
        title: 'Auditoría global',
        description: 'Bandeja de supervisión para el administrador: cancelaciones justificadas, transferencias y cuentas inhabilitadas.',
    },
    {
        icon: Wallet,
        title: 'Múltiples pagos',
        description: 'Efectivo, transferencia con comprobante y tarjeta, todo trazado venta por venta.',
    },
];

const businessTypes = [
    {
        icon: Store,
        title: 'Tiendas & Minimarkets',
        description: 'Caja rápida, inventario controlado y clientes fidelizados con puntos.',
    },
    {
        icon: NotebookPen,
        title: 'Papelerías',
        description: 'Catálogo de útiles, stock por temporada escolar y ventas trazables.',
    },
    {
        icon: Scissors,
        title: 'Barberías & Salones',
        description: 'Agenda online, turnero en vivo, servicios con precio y recordatorios automáticos.',
    },
];
</script>

<template>
    <Head title="NovaSaaS - Gestión para tu Negocio" />

    <div class="min-h-screen bg-background text-foreground flex flex-col selection:bg-primary selection:text-primary-foreground">

        <!-- Navbar Superior -->
        <header class="border-b bg-card/60 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-lg bg-primary flex items-center justify-center text-primary-foreground font-bold shadow-md">
                        <Layers class="h-5 w-5" />
                    </div>
                    <span class="font-bold text-lg tracking-tight">
                        NovaSaaS
                    </span>
                    <Badge variant="secondary" class="hidden sm:inline-flex text-xs">
                        Multi-tenant
                    </Badge>
                </div>

                <div class="flex items-center gap-3">
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
                            Plataforma en vivo · {{ props.stats.businesses }} {{ props.stats.businesses === 1 ? 'negocio operando' : 'negocios operando' }}
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                        Bienvenido a NovaSaaS.
                    </h1>

                    <p class="text-lg text-muted-foreground leading-relaxed">
                        La plataforma que le da trazabilidad total a tu negocio: cada venta, cada producto
                        y cada cita quedan registrados, medidos y bajo control. Menos cuadernos, más crecimiento.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <template v-if="!$page.props.auth?.user">
                            <Link :href="register.url()">
                                <Button size="lg">
                                    Crear mi Negocio Gratis
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                            <Link :href="login.url()">
                                <Button variant="outline" size="lg">
                                    Iniciar Sesión
                                </Button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="dashboard.url()">
                                <Button size="lg">
                                    Ir al Dashboard
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </template>
                    </div>

                    <div class="grid grid-cols-3 gap-4 pt-4 border-t">
                        <div>
                            <div class="text-2xl font-bold text-primary">{{ props.stats.businesses }}</div>
                            <div class="text-xs text-muted-foreground">Negocios registrados</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">{{ props.stats.appointments }}</div>
                            <div class="text-xs text-muted-foreground">Citas gestionadas</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">{{ props.stats.points }}</div>
                            <div class="text-xs text-muted-foreground">Puntos otorgados</div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de propuesta de valor -->
                <div class="lg:col-span-5">
                    <Card class="shadow-xl border-primary/20 bg-card">
                        <CardHeader>
                            <CardTitle class="text-xl flex items-center gap-2">
                                <TrendingUp class="h-5 w-5 text-primary" />
                                Trazabilidad total
                            </CardTitle>
                            <CardDescription>
                                Todo lo que pasa en tu negocio, en un solo panel.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-for="(item, idx) in [
                                'Ventas e inventario sincronizados al instante',
                                'Alertas de stock mínimo configurables',
                                'Clientes y puntos de fidelización',
                                'Agenda con auditoría y justificaciones',
                                'Reportes financieros de 7 días',
                            ]" :key="idx" class="flex items-start gap-3">
                                <div class="rounded-full bg-green-500/10 p-1 text-green-600 dark:text-green-400 mt-0.5">
                                    <Check class="h-4 w-4" />
                                </div>
                                <p class="text-sm">{{ item }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </section>

        <!-- Tipos de negocio -->
        <section class="py-16 max-w-6xl mx-auto px-4 w-full">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <Badge variant="outline" class="mb-2">Hecho para tu tipo de negocio</Badge>
                <h2 class="text-3xl font-bold tracking-tight">Un sistema, tres formas de crecer</h2>
                <p class="text-muted-foreground mt-2">
                    El menú, el dashboard y las herramientas se adaptan automáticamente al tipo de negocio que registres.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card v-for="(type, idx) in businessTypes" :key="idx" class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <div class="h-11 w-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-2">
                            <component :is="type.icon" class="h-6 w-6" />
                        </div>
                        <CardTitle class="text-lg font-bold">{{ type.title }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ type.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Funcionalidades -->
        <section class="py-16 border-t bg-muted/20">
            <div class="max-w-6xl mx-auto px-4 w-full">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <Badge variant="outline" class="mb-2">Capacidades</Badge>
                    <h2 class="text-3xl font-bold tracking-tight">Todo lo que tu negocio necesita</h2>
                    <p class="text-muted-foreground mt-2">
                        Operación diaria, fidelización y supervisión en una sola plataforma multi-tenant.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card v-for="(feature, idx) in features" :key="idx" class="hover:shadow-md transition-shadow bg-card">
                        <CardHeader>
                            <div class="h-10 w-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-2">
                                <component :is="feature.icon" class="h-5 w-5" />
                            </div>
                            <CardTitle class="text-base font-bold">{{ feature.title }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ feature.description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- CTA final -->
        <section class="py-16 max-w-6xl mx-auto px-4 w-full">
            <Card class="border-primary/20 bg-linear-to-b from-primary/10 to-background text-center py-12 px-6">
                <h2 class="text-3xl font-bold tracking-tight">Registra tu negocio en minutos</h2>
                <p class="text-muted-foreground mt-2 max-w-xl mx-auto">
                    Crea tu cuenta como administrador, configura tu inventario o tus servicios,
                    y empieza a vender con trazabilidad total desde el primer día.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                    <template v-if="!$page.props.auth?.user">
                        <Link :href="register.url()">
                            <Button size="lg">
                                Registrar Negocio
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="dashboard.url()">
                            <Button size="lg">
                                Ir al Dashboard
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </template>
                </div>
            </Card>
        </section>

        <!-- Footer -->
        <footer class="border-t py-8 mt-auto bg-muted/20">
            <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-muted-foreground">
                <div class="flex items-center gap-2">
                    <Layers class="h-4 w-4 text-primary" />
                    <span>NovaSaaS - Trazabilidad de ventas e inventarios para negocios locales</span>
                </div>
                <div>
                    <span>© {{ new Date().getFullYear() }} NovaSaaS. Todos los derechos reservados.</span>
                </div>
            </div>
        </footer>

    </div>
</template>
