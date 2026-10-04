<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    ShoppingCart, 
    Plus, 
    ExternalLink, 
    Eye, 
    Receipt, 
    CreditCard, 
    Banknote, 
    ArrowRightLeft 
} from '@lucide/vue';

interface SaleItem {
    id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    product: {
        id: number;
        name: string;
    };
}

interface Sale {
    id: number;
    total_amount: number;
    payment_method: 'efectivo' | 'transferencia' | 'tarjeta';
    payment_proof_path: string | null;
    created_at: string;
    user: {
        name: string;
    };
    items: SaleItem[];
}

const props = defineProps<{
    sales: {
        data: Sale[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}>();

const selectedSale = ref<Sale | null>(null);
const isDetailModalOpen = ref(false);

const viewSaleDetail = (sale: Sale) => {
    selectedSale.value = sale;
    isDetailModalOpen.value = true;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
</script>

<template>
    <Head title="Historial de Ventas" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                    <Receipt class="h-8 w-8 text-primary" />
                    Historial de Ventas y Comprobantes
                </h1>
                <p class="text-muted-foreground mt-1">
                    Consulta el registro completo de ventas, tickets emitidos y comprobantes bancarios.
                </p>
            </div>

            <Link href="/sales/create">
                <Button class="gap-2">
                    <Plus class="h-4 w-4" />
                    Nueva Venta (POS)
                </Button>
            </Link>
        </div>

        <!-- Tabla de Ventas -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Transacciones Realizadas</CardTitle>
                <CardDescription>
                    Listado cronológico de ventas efectuadas en tu negocio.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="props.sales.data.length === 0" class="text-center py-12 text-muted-foreground">
                    <ShoppingCart class="h-12 w-12 mx-auto mb-3 opacity-40" />
                    No hay ventas registradas aún. Abre el Punto de Venta para procesar la primera compra.
                </div>
                <div v-else class="space-y-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[90px]">Ticket #</TableHead>
                                <TableHead>Fecha y Hora</TableHead>
                                <TableHead>Cajero / Vendedor</TableHead>
                                <TableHead>Método de Pago</TableHead>
                                <TableHead>Ítems</TableHead>
                                <TableHead>Total Cobrado</TableHead>
                                <TableHead class="text-right">Detalle</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="sale in props.sales.data" :key="sale.id">
                                <TableCell class="font-bold">#{{ sale.id }}</TableCell>
                                <TableCell class="text-sm">{{ formatDate(sale.created_at) }}</TableCell>
                                <TableCell class="font-medium">{{ sale.user?.name }}</TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="sale.payment_method === 'efectivo' ? 'secondary' : sale.payment_method === 'transferencia' ? 'outline' : 'default'"
                                        class="capitalize flex w-fit items-center gap-1.5"
                                    >
                                        <Banknote v-if="sale.payment_method === 'efectivo'" class="h-3 w-3 text-green-600" />
                                        <ArrowRightLeft v-else-if="sale.payment_method === 'transferencia'" class="h-3 w-3 text-blue-600" />
                                        <CreditCard v-else class="h-3 w-3" />
                                        {{ sale.payment_method }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ sale.items?.length ?? 0 }} producto(s)
                                </TableCell>
                                <TableCell class="font-bold text-base text-primary">
                                    {{ formatCurrency(sale.total_amount) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button variant="ghost" size="sm" class="gap-1" @click="viewSaleDetail(sale)">
                                        <Eye class="h-4 w-4" />
                                        Ver Ticket
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginación -->
                    <div v-if="props.sales.links.length > 3" class="flex justify-center gap-1 pt-4">
                        <template v-for="(link, key) in props.sales.links" :key="key">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 text-sm rounded border transition-colors"
                                :class="link.active ? 'bg-primary text-primary-foreground border-primary font-bold' : 'hover:bg-muted text-foreground'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Modal de Detalle de Ticket -->
        <Dialog v-model:open="isDetailModalOpen">
            <DialogContent class="sm:max-w-[550px]" v-if="selectedSale">
                <DialogHeader>
                    <DialogTitle class="flex items-center justify-between">
                        <span>Detalle de Venta #{{ selectedSale.id }}</span>
                        <span class="text-primary font-bold text-lg">{{ formatCurrency(selectedSale.total_amount) }}</span>
                    </DialogTitle>
                    <DialogDescription>
                        Emitido por {{ selectedSale.user?.name }} el {{ formatDate(selectedSale.created_at) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <!-- Resumen del Pago -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-muted/40 text-sm">
                        <span>Método de pago: <strong class="capitalize">{{ selectedSale.payment_method }}</strong></span>
                        <div v-if="selectedSale.payment_proof_path">
                            <a 
                                :href="`/storage/${selectedSale.payment_proof_path}`" 
                                target="_blank"
                                class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline font-semibold"
                            >
                                <ExternalLink class="h-3.5 w-3.5" />
                                Ver Comprobante Adjunto
                            </a>
                        </div>
                    </div>

                    <!-- Lista de Ítems Vendidos -->
                    <div class="border rounded-md overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Producto</TableHead>
                                    <TableHead class="text-center">Cant.</TableHead>
                                    <TableHead class="text-right">P. Unitario</TableHead>
                                    <TableHead class="text-right">Subtotal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in selectedSale.items" :key="item.id">
                                    <TableCell class="font-medium">{{ item.product?.name ?? 'Producto' }}</TableCell>
                                    <TableCell class="text-center">{{ item.quantity }}</TableCell>
                                    <TableCell class="text-right">{{ formatCurrency(item.unit_price) }}</TableCell>
                                    <TableCell class="text-right font-semibold">{{ formatCurrency(item.subtotal) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="flex justify-between items-center pt-2 font-bold text-base">
                        <span>Total de la Transacción:</span>
                        <span class="text-primary text-xl">{{ formatCurrency(selectedSale.total_amount) }}</span>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
