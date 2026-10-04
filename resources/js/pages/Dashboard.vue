<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    ArrowUpRight, 
    ArrowDownRight, 
    Users, 
    DollarSign, 
    Calendar, 
    Activity, 
    PackageOpen,
    ShieldAlert,
    Clock,
    Scissors,
    Store
} from '@lucide/vue';

const props = defineProps<{
    metrics: {
        todaySales: number;
        yesterdaySales: number;
        salesChange: number;
        newCustomersCount: number;
        totalPoints: number;
        lowStockCount: number;
        auditPendingCount: number;
        chart: {
            categories: string[];
            series: Array<{
                name: string;
                data: number[];
            }>;
        };
    };
    upcomingAppointments: Array<{
        id: number;
        start_time: string;
        status: string;
        customer: { name: string };
        user: { name: string };
    }>;
    lowStockProducts: Array<{
        id: number;
        name: string;
        stock: number;
        min_stock: number;
        price: number;
    }>;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const business = computed(() => user.value?.business);
const isBarber = computed(() => business.value?.type === 'barberia');
const isAdmin = computed(() => user.value?.role === 'admin');

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Configuración de ApexCharts adaptada al diseño moderno
const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 260,
        toolbar: { show: false },
        fontFamily: 'inherit',
        background: 'transparent',
    },
    colors: ['#3b82f6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 95, 100],
        },
    },
    stroke: {
        curve: 'smooth',
        width: 3,
    },
    dataLabels: {
        enabled: false,
    },
    grid: {
        borderColor: '#e2e8f0',
        strokeDashArray: 4,
        padding: {
            left: 10,
            right: 10,
        },
    },
    xaxis: {
        categories: props.metrics.chart.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: {
                colors: '#64748b',
                fontSize: '12px',
            },
        },
    },
    yaxis: {
        labels: {
            formatter: (val: number) => `$${(val / 1000).toFixed(0)}k`,
            style: {
                colors: '#64748b',
                fontSize: '12px',
            },
        },
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: (val: number) => formatCurrency(val),
        },
    },
}));

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Panel Principal',
                href: dashboard.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Panel Principal" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-8">
        
        <!-- Header del Dashboard -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-3xl font-bold tracking-tight">Bienvenido, {{ user?.name }}</h2>
                    <Badge variant="outline" class="capitalize flex items-center gap-1">
                        <component :is="isBarber ? Scissors : Store" class="h-3.5 w-3.5" />
                        {{ business?.name ?? 'Mi Negocio' }} ({{ business?.type }})
                    </Badge>
                </div>
                <p class="text-muted-foreground mt-1">Resumen financiero y operativo en tiempo real.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <Link 
                    v-if="!isBarber" 
                    href="/sales/create" 
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                >
                    Nueva Venta (POS)
                </Link>
                <Link 
                    v-else 
                    href="/appointments" 
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                >
                    Agendar Cita
                </Link>
            </div>
        </div>

        <!-- Bento Grid Principal -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-min">
            
            <!-- Tarjeta 1: Gráfico Financiero de Ingresos (ApexCharts) -->
            <Card class="lg:col-span-2 lg:row-span-2 flex flex-col justify-between overflow-hidden shadow-sm hover:shadow-md transition-all">
                <CardHeader class="pb-2">
                    <CardTitle class="text-lg font-medium flex items-center justify-between">
                        Rendimiento de Ventas (Últimos 7 Días)
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardTitle>
                    <CardDescription>
                        Ingresos diarios registrados en el sistema.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex-1 pt-2">
                    <apexchart
                        type="area"
                        height="260"
                        :options="chartOptions"
                        :series="props.metrics.chart.series"
                    />
                </CardContent>
            </Card>

            <!-- Tarjeta 2: Ventas de Hoy -->
            <Card class="flex flex-col justify-between shadow-sm hover:shadow-md transition-all">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Ventas Hoy</CardTitle>
                    <DollarSign class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-primary">{{ formatCurrency(props.metrics.todaySales) }}</div>
                    <p class="text-xs mt-1 flex items-center" :class="props.metrics.salesChange >= 0 ? 'text-green-600' : 'text-red-500'">
                        <component :is="props.metrics.salesChange >= 0 ? ArrowUpRight : ArrowDownRight" class="mr-1 h-3.5 w-3.5" />
                        {{ props.metrics.salesChange >= 0 ? '+' : '' }}{{ props.metrics.salesChange }}% respecto a ayer
                    </p>
                </CardContent>
            </Card>

            <!-- Tarjeta 3: Fidelización y Clientes -->
            <Card class="flex flex-col justify-between shadow-sm hover:shadow-md transition-all">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Fidelización de Clientes</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ props.metrics.newCustomersCount }} clientes</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Puntos activos acumulados: <span class="font-semibold text-primary">{{ props.metrics.totalPoints }} pts</span>
                    </p>
                </CardContent>
            </Card>

            <!-- Tarjeta 4: Próximas Citas (Barbería) O Inventario Crítico (Tienda/Papelería) -->
            <Card v-if="isBarber" class="lg:col-span-2 shadow-sm hover:shadow-md transition-all">
                <CardHeader class="pb-2 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-primary" />
                            Próximas Citas en Barbería
                        </CardTitle>
                        <CardDescription>Citas agendadas para las próximas horas.</CardDescription>
                    </div>
                    <Link href="/turnero" class="text-xs text-blue-600 hover:underline font-medium">Ver Pantalla TV</Link>
                </CardHeader>
                <CardContent>
                    <div v-if="props.upcomingAppointments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        No hay citas pendientes para hoy.
                    </div>
                    <div v-else class="space-y-3">
                        <div 
                            v-for="apt in props.upcomingAppointments" 
                            :key="apt.id"
                            class="flex items-center justify-between border-b pb-2 last:border-0 last:pb-0"
                        >
                            <div class="flex items-center gap-3">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium leading-none">{{ apt.customer?.name }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">Barbero: {{ apt.user?.name }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge variant="secondary" class="text-xs">{{ formatTime(apt.start_time) }}</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Si es Tienda / Papelería: Productos con Bajo Stock -->
            <Card v-else class="lg:col-span-2 shadow-sm hover:shadow-md transition-all">
                <CardHeader class="pb-2 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <PackageOpen class="h-4 w-4 text-yellow-600" />
                            Control de Stock Crítico
                        </CardTitle>
                        <CardDescription>Productos que requieren reabastecimiento pronto.</CardDescription>
                    </div>
                    <Link href="/products" class="text-xs text-primary hover:underline font-medium">Ir a Inventario</Link>
                </CardHeader>
                <CardContent>
                    <div v-if="props.lowStockProducts.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Inventario en óptimas condiciones. Sin stock bajo.
                    </div>
                    <div v-else class="space-y-2">
                        <div 
                            v-for="prod in props.lowStockProducts" 
                            :key="prod.id"
                            class="flex items-center justify-between text-sm py-1 border-b last:border-0"
                        >
                            <span class="font-medium truncate max-w-[200px]">{{ prod.name }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400">
                                {{ prod.stock }} disponibles
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tarjeta 5: Alerta de Auditoría Global (Solo Visible para Administrador) -->
            <Card 
                v-if="isAdmin" 
                class="lg:col-span-4 flex flex-col md:flex-row md:items-center justify-between shadow-sm hover:shadow-md transition-all border-l-4 border-l-blue-600 bg-muted/10 p-4"
            >
                <div class="flex items-center gap-4">
                    <div class="bg-blue-100 p-3 rounded-full text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                        <ShieldAlert class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-base font-semibold">Auditoría y Supervisión de Operaciones</h4>
                        <p class="text-sm text-muted-foreground">
                            Hay {{ props.metrics.auditPendingCount }} registros entre cancelaciones de citas con justificación y comprobantes de transferencias bancarias listos para supervisión.
                        </p>
                    </div>
                </div>
                <div class="mt-3 md:mt-0">
                    <Link 
                        href="/audit" 
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                    >
                        Abrir Bandeja de Auditoría
                        <ArrowUpRight class="h-4 w-4" />
                    </Link>
                </div>
            </Card>
            
        </div>
    </div>
</template>
