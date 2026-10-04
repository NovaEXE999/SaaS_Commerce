<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ShieldAlert,
    Calendar,
    Receipt,
    PackageX,
    UserX,
    ExternalLink,
    CheckCircle2
} from '@lucide/vue';

const props = defineProps<{
    appointmentAudits: Array<{
        id: number;
        status: string;
        justification: string | null;
        start_time: string;
        updated_at: string;
        customer: { name: string; phone: string | null };
        user: { name: string };
    }>;
    transferSales: Array<{
        id: number;
        total_amount: number;
        payment_method: string;
        payment_proof_path: string | null;
        created_at: string;
        user: { name: string };
    }>;
    deletedProducts: Array<{
        id: number;
        name: string;
        price: number;
        stock: number;
        deleted_at: string;
    }>;
    disabledEmployees: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
        deleted_at: string;
    }>;
}>();

const activeTab = ref<'appointments' | 'transfers' | 'products' | 'employees'>('appointments');

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
};
</script>

<template>
    <Head title="Bandeja de Auditoría" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                    <ShieldAlert class="h-8 w-8 text-primary" />
                    Bandeja de Auditoría
                </h1>
                <p class="text-muted-foreground mt-1">
                    Supervisión global de movimientos sensibles, justificaciones obligatorias y comprobantes de pago.
                </p>
            </div>
            
            <div class="flex items-center gap-2 bg-muted p-1 rounded-lg">
                <Button 
                    variant="ghost" 
                    size="sm" 
                    :class="{ 'bg-background shadow-xs font-semibold': activeTab === 'appointments' }"
                    @click="activeTab = 'appointments'"
                >
                    <Calendar class="mr-1.5 h-4 w-4" />
                    Citas ({{ props.appointmentAudits.length }})
                </Button>
                <Button 
                    variant="ghost" 
                    size="sm" 
                    :class="{ 'bg-background shadow-xs font-semibold': activeTab === 'transfers' }"
                    @click="activeTab = 'transfers'"
                >
                    <Receipt class="mr-1.5 h-4 w-4" />
                    Transferencias ({{ props.transferSales.length }})
                </Button>
                <Button 
                    variant="ghost" 
                    size="sm" 
                    :class="{ 'bg-background shadow-xs font-semibold': activeTab === 'products' }"
                    @click="activeTab = 'products'"
                >
                    <PackageX class="mr-1.5 h-4 w-4" />
                    Productos Inhabilitados ({{ props.deletedProducts.length }})
                </Button>
                <Button 
                    variant="ghost" 
                    size="sm" 
                    :class="{ 'bg-background shadow-xs font-semibold': activeTab === 'employees' }"
                    @click="activeTab = 'employees'"
                >
                    <UserX class="mr-1.5 h-4 w-4" />
                    Empleados ({{ props.disabledEmployees.length }})
                </Button>
            </div>
        </div>

        <!-- Tab 1: Citas Modificadas / Canceladas -->
        <Card v-if="activeTab === 'appointments'">
            <CardHeader>
                <CardTitle class="text-lg">Citas Canceladas o Reagendadas</CardTitle>
                <CardDescription>
                    Registro de citas con justificación requerida por auditoría de personal.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="props.appointmentAudits.length === 0" class="text-center py-10 text-muted-foreground">
                    <CheckCircle2 class="h-10 w-10 mx-auto mb-2 text-green-500 opacity-60" />
                    No hay cancelaciones ni reagendamientos registrados.
                </div>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fecha Cita</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Barbero</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-1/3">Justificación Registrada</TableHead>
                            <TableHead>Fecha Modificación</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="audit in props.appointmentAudits" :key="audit.id">
                            <TableCell class="font-medium">{{ formatDate(audit.start_time) }}</TableCell>
                            <TableCell>{{ audit.customer?.name ?? 'Sin cliente' }}</TableCell>
                            <TableCell>{{ audit.user?.name ?? 'No asignado' }}</TableCell>
                            <TableCell>
                                <Badge :variant="audit.status === 'cancelada' ? 'destructive' : 'secondary'">
                                    {{ audit.status.toUpperCase() }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm italic text-muted-foreground bg-muted/30 rounded p-2">
                                "{{ audit.justification ?? 'Sin justificación registrada' }}"
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ formatDate(audit.updated_at) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Tab 2: Comprobantes de Transferencia -->
        <Card v-if="activeTab === 'transfers'">
            <CardHeader>
                <CardTitle class="text-lg">Validación de Comprobantes de Pago</CardTitle>
                <CardDescription>
                    Ventas procesadas mediante transferencia bancaria con soporte adjunto.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="props.transferSales.length === 0" class="text-center py-10 text-muted-foreground">
                    <CheckCircle2 class="h-10 w-10 mx-auto mb-2 text-green-500 opacity-60" />
                    No hay ventas registradas por transferencia bancaria.
                </div>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Ticket #</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Cajero / Vendedor</TableHead>
                            <TableHead>Monto Total</TableHead>
                            <TableHead>Comprobante Adjunto</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="sale in props.transferSales" :key="sale.id">
                            <TableCell class="font-bold">#{{ sale.id }}</TableCell>
                            <TableCell>{{ formatDate(sale.created_at) }}</TableCell>
                            <TableCell>{{ sale.user?.name }}</TableCell>
                            <TableCell class="font-semibold text-primary">{{ formatCurrency(sale.total_amount) }}</TableCell>
                            <TableCell>
                                <a 
                                    v-if="sale.payment_proof_path" 
                                    :href="`/storage/${sale.payment_proof_path}`" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:underline font-medium"
                                >
                                    <ExternalLink class="h-3.5 w-3.5" />
                                    Ver Pantallazo de Pago
                                </a>
                                <span v-else class="text-xs text-muted-foreground">Sin comprobante</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Tab 3: Productos Inhabilitados -->
        <Card v-if="activeTab === 'products'">
            <CardHeader>
                <CardTitle class="text-lg">Productos Inhabilitados (Papelera)</CardTitle>
                <CardDescription>
                    Productos que han sido retirados de la venta activa mediante Soft Deletes.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="props.deletedProducts.length === 0" class="text-center py-10 text-muted-foreground">
                    <CheckCircle2 class="h-10 w-10 mx-auto mb-2 text-green-500 opacity-60" />
                    No hay productos inhabilitados en este negocio.
                </div>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Producto</TableHead>
                            <TableHead>Precio</TableHead>
                            <TableHead>Stock al inhabilitar</TableHead>
                            <TableHead>Fecha Inhabilitación</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="prod in props.deletedProducts" :key="prod.id">
                            <TableCell class="font-medium">{{ prod.name }}</TableCell>
                            <TableCell>{{ formatCurrency(prod.price) }}</TableCell>
                            <TableCell>{{ prod.stock }} unid.</TableCell>
                            <TableCell class="text-muted-foreground text-xs">{{ formatDate(prod.deleted_at) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Tab 4: Empleados Inhabilitados -->
        <Card v-if="activeTab === 'employees'">
            <CardHeader>
                <CardTitle class="text-lg">Cuentas de Empleados Inhabilitadas</CardTitle>
                <CardDescription>
                    Usuarios del personal con acceso revocado mediante Soft Deletes.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="props.disabledEmployees.length === 0" class="text-center py-10 text-muted-foreground">
                    <CheckCircle2 class="h-10 w-10 mx-auto mb-2 text-green-500 opacity-60" />
                    No hay cuentas de empleados inhabilitadas.
                </div>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Correo Electrónico</TableHead>
                            <TableHead>Rol</TableHead>
                            <TableHead>Fecha Inhabilitación</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="emp in props.disabledEmployees" :key="emp.id">
                            <TableCell class="font-medium">{{ emp.name }}</TableCell>
                            <TableCell>{{ emp.email }}</TableCell>
                            <TableCell><Badge variant="outline">{{ emp.role }}</Badge></TableCell>
                            <TableCell class="text-muted-foreground text-xs">{{ formatDate(emp.deleted_at) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
