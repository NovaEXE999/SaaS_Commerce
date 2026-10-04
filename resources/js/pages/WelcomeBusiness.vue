<script setup lang="ts">
import { computed } from 'vue';
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
    Store,
    NotebookPen,
    ShoppingBag,
    PackageCheck,
    Gift,
    BellRing,
    ShieldCheck,
    ArrowRight,
} from '@lucide/vue';

const props = defineProps<{
    business: {
        id: number;
        name: string;
        type: string;
    };
    products?: Array<{
        id: number;
        name: string;
        description: string | null;
        price: string;
        stock: number;
    }>;
}>();

const isPapeleria = computed(() => props.business.type === 'papeleria');

const content = computed(() => isPapeleria.value ? {
    badge: 'Papelería & Útiles',
    title: 'Todo para tu estudio y oficina en un solo lugar.',
    subtitle: 'Explora el catálogo destacado, acumula puntos con cada compra y recibe atención personalizada en cada visita.',
    catalogTitle: 'Catálogo destacado',
    catalogSubtitle: 'Una muestra de los productos disponibles en tienda.',
} : {
    badge: 'Tienda & Minimarket',
    title: 'Tu tienda de barrio, ahora más cerca de ti.',
    subtitle: 'Explora el catálogo destacado, acumula puntos con cada compra y paga como prefieras: efectivo, transferencia o tarjeta.',
    catalogTitle: 'Catálogo destacado',
    catalogSubtitle: 'Una muestra de los productos disponibles en tienda.',
});

const formatPrice = (value: string | number) => {
    const amount = typeof value === 'number' ? value : parseFloat(value ?? 0);
    return isNaN(amount) ? '$0' : `$${amount.toLocaleString('es-CO')}`;
};
</script>

<template>
    <Head :title="`${props.business.name} - Catálogo`" />

    <div class="min-h-screen bg-background text-foreground flex flex-col selection:bg-primary selection:text-primary-foreground">

        <!-- Navbar Superior -->
        <header class="border-b bg-card/60 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-lg bg-primary flex items-center justify-center text-primary-foreground font-bold shadow-md">
                        <component :is="isPapeleria ? NotebookPen : Store" class="h-5 w-5" />
                    </div>
                    <span class="font-bold text-lg tracking-tight">
                        {{ props.business.name }}
                    </span>
                    <Badge variant="secondary" class="hidden sm:inline-flex text-xs">
                        {{ content.badge }}
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
                            Catálogo en línea<span v-if="props.products?.length"> · {{ props.products.length }} {{ props.products.length === 1 ? 'producto destacado' : 'productos destacados' }}</span>
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                        {{ content.title }}
                    </h1>

                    <p class="text-lg text-muted-foreground leading-relaxed">
                        {{ content.subtitle }}
                    </p>

                    <div class="grid grid-cols-3 gap-4 pt-4 border-t">
                        <div>
                            <div class="text-2xl font-bold text-primary">POS</div>
                            <div class="text-xs text-muted-foreground">Venta rápida en caja</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">Stock</div>
                            <div class="text-xs text-muted-foreground">Inventario al día</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">Puntos</div>
                            <div class="text-xs text-muted-foreground">1 punto por cada $5.000</div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de beneficios -->
                <div class="lg:col-span-5">
                    <Card class="shadow-xl border-primary/20 bg-card">
                        <CardHeader>
                            <CardTitle class="text-xl flex items-center gap-2">
                                <ShoppingBag class="h-5 w-5 text-primary" />
                                Beneficios para clientes
                            </CardTitle>
                            <CardDescription>
                                Comprar aquí tiene recompensa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                    <Gift class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-semibold text-sm">Programa de puntos</p>
                                    <p class="text-sm text-muted-foreground">Cada $5.000 en compras te da 1 punto canjeable por descuentos.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                    <PackageCheck class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-semibold text-sm">Disponibilidad garantizada</p>
                                    <p class="text-sm text-muted-foreground">Inventario controlado para que siempre encuentres lo que buscas.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                    <BellRing class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-semibold text-sm">Atención personalizada</p>
                                    <p class="text-sm text-muted-foreground">Tus compras quedan registradas para una mejor atención.</p>
                                </div>
                            </div>
                            <template v-if="!$page.props.auth?.user">
                                <Link :href="register.url()">
                                    <Button class="w-full mt-2" size="lg">
                                        Registrar mi Negocio
                                        <ArrowRight class="ml-2 h-4 w-4" />
                                    </Button>
                                </Link>
                            </template>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </section>

        <!-- Sección de Catálogo -->
        <section class="py-16 max-w-6xl mx-auto px-4 w-full">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <Badge variant="outline" class="mb-2">Nuestro Catálogo</Badge>
                <h2 class="text-3xl font-bold tracking-tight">{{ content.catalogTitle }}</h2>
                <p class="text-muted-foreground mt-2">
                    {{ content.catalogSubtitle }}
                </p>
            </div>

            <div v-if="props.products && props.products.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <Card v-for="product in props.products" :key="product.id" class="hover:shadow-md transition-shadow">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-bold truncate">{{ product.name }}</CardTitle>
                        <CardDescription class="truncate text-xs">
                            {{ product.description || 'Producto disponible en tienda' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <span class="text-lg font-extrabold text-primary">{{ formatPrice(product.price) }}</span>
                        <Badge variant="secondary" class="text-xs">{{ product.stock }} disp.</Badge>
                    </CardContent>
                </Card>
            </div>
            <div v-else class="text-center py-12 text-muted-foreground">
                <ShoppingBag class="h-10 w-10 mx-auto mb-3 opacity-50" />
                <p>El catálogo estará disponible muy pronto. Visítanos en tienda.</p>
            </div>
        </section>

        <!-- Franja de confianza -->
        <section class="border-t bg-muted/20 py-10">
            <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-center gap-6 text-sm text-muted-foreground">
                <span class="flex items-center gap-2">
                    <ShieldCheck class="h-4 w-4 text-primary" />
                    Compra protegida y registrada
                </span>
                <span class="flex items-center gap-2">
                    <Gift class="h-4 w-4 text-primary" />
                    Puntos en cada compra
                </span>
                <span class="flex items-center gap-2">
                    <PackageCheck class="h-4 w-4 text-primary" />
                    Stock actualizado
                </span>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-8 mt-auto bg-muted/20">
            <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-muted-foreground">
                <div class="flex items-center gap-2">
                    <component :is="isPapeleria ? NotebookPen : Store" class="h-4 w-4 text-primary" />
                    <span>{{ props.business.name }} · Impulsado por NovaSaaS</span>
                </div>
                <div>
                    <span>© {{ new Date().getFullYear() }} {{ props.business.name }}. Todos los derechos reservados.</span>
                </div>
            </div>
        </footer>

    </div>
</template>
