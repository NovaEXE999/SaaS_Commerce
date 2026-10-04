<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Calendar, Plus, CheckCircle, XCircle, Clock } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
    appointments: any[];
    customers: any[];
    barbers: any[];
    services: any[];
}>();

// Estado del Modal de Nueva Cita
const isCreateModalOpen = ref(false);
const createForm = useForm({
    customer_id: '',
    user_id: '',
    service_id: '',
    start_time: '',
});

// Estado del Modal de Actualización de Estado (Justificación / Aprobación)
const isStatusModalOpen = ref(false);
const selectedAppointment = ref<any>(null);
const targetStatus = ref('');
const statusForm = useForm({
    status: '',
    justification: '',
    client_approved: false,
    payment_method: 'efectivo',
});

const formatPrice = (value: any) => {
    const amount = parseFloat(value ?? 0);
    return isNaN(amount) ? '$0' : `$${amount.toLocaleString()}`;
};

const openStatusModal = (appointment: any, status: string) => {
    selectedAppointment.value = appointment;
    targetStatus.value = status;
    statusForm.status = status;
    statusForm.justification = '';
    statusForm.client_approved = false;
    statusForm.payment_method = 'efectivo';
    isStatusModalOpen.value = true;
};

const updateStatus = () => {
    statusForm.put(`/appointments/${selectedAppointment.value.id}/status`, {
        onSuccess: () => {
            isStatusModalOpen.value = false;
        }
    });
};

const submitCreate = () => {
    createForm.post('/appointments', {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        }
    });
};

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Agenda de Citas', href: '/appointments' }] } });
</script>

<template>
    <Head title="Agenda de Barbería" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold">Agenda de Citas</h2>
                <p class="text-muted-foreground">Controla los turnos y el televisor en tiempo real.</p>
            </div>
            <div class="flex gap-4">
                <Button variant="outline" as-child>
                    <a href="/turnero" target="_blank">Ver Pantalla TV</a>
                </Button>
                <Button @click="isCreateModalOpen = true">
                    <Plus class="mr-2 h-4 w-4" /> Agendar Cita
                </Button>
            </div>
        </div>

        <Card>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fecha y Hora</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Barbero</TableHead>
                            <TableHead>Servicio</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="app in appointments" :key="app.id">
                            <TableCell class="font-medium">
                                {{ new Date(app.start_time).toLocaleString() }}
                            </TableCell>
                            <TableCell>{{ app.customer.name }}</TableCell>
                            <TableCell>{{ app.user.name }}</TableCell>
                            <TableCell>
                                <span v-if="app.service">{{ app.service.name }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                                <span class="block text-xs text-muted-foreground">{{ formatPrice(app.price) }}</span>
                            </TableCell>
                            <TableCell>
                                <span class="px-2 py-1 rounded text-xs font-bold uppercase"
                                    :class="{
                                        'bg-blue-100 text-blue-800': app.status === 'agendada',
                                        'bg-orange-100 text-orange-800': app.status === 'adelantada',
                                        'bg-yellow-100 text-yellow-800': app.status === 'reagendada',
                                        'bg-red-100 text-red-800': app.status === 'cancelada',
                                        'bg-green-100 text-green-800': app.status === 'completada',
                                    }">
                                    {{ app.status }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2" v-if="app.status === 'agendada' || app.status === 'adelantada' || app.status === 'reagendada'">
                                    <!-- Adelantar -->
                                    <Button variant="outline" size="sm" @click="openStatusModal(app, 'adelantada')" title="Adelantar Turno">
                                        <Clock class="h-4 w-4 text-orange-500" />
                                    </Button>
                                    <!-- Cancelar -->
                                    <Button variant="outline" size="sm" @click="openStatusModal(app, 'cancelada')" title="Cancelar">
                                        <XCircle class="h-4 w-4 text-red-500" />
                                    </Button>
                                    <!-- Completar -->
                                    <Button variant="outline" size="sm" @click="openStatusModal(app, 'completada')" title="Completada">
                                        <CheckCircle class="h-4 w-4 text-green-500" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>

    <!-- Modal Crear Cita -->
    <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Agendar Nueva Cita</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="space-y-2">
                    <Label>Cliente</Label>
                    <Select v-model="createForm.customer_id" required>
                        <SelectTrigger><SelectValue placeholder="Seleccione un cliente" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="c in customers" :key="c.id" :value="c.id.toString()">{{ c.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        ¿El cliente no existe?
                        <Link href="/customers" class="text-primary hover:underline font-medium">Créalo aquí</Link>
                    </p>
                    <span class="text-sm text-red-500" v-if="createForm.errors.customer_id">{{ createForm.errors.customer_id }}</span>
                </div>
                <div class="space-y-2">
                    <Label>Barbero Asignado</Label>
                    <Select v-model="createForm.user_id" required>
                        <SelectTrigger><SelectValue placeholder="Seleccione un barbero" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="b in barbers" :key="b.id" :value="b.id.toString()">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="space-y-2">
                    <Label>Servicio</Label>
                    <Select v-model="createForm.service_id" required>
                        <SelectTrigger><SelectValue placeholder="Seleccione un servicio" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="s in services" :key="s.id" :value="s.id.toString()">{{ s.name }} ({{ formatPrice(s.price) }})</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        ¿Falta un servicio?
                        <Link href="/services" class="text-primary hover:underline font-medium">Créalo aquí</Link>
                    </p>
                    <span class="text-sm text-red-500" v-if="createForm.errors.service_id">{{ createForm.errors.service_id }}</span>
                </div>
                <div class="space-y-2">
                    <Label>Fecha y Hora</Label>
                    <Input type="datetime-local" v-model="createForm.start_time" required />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isCreateModalOpen = false">Cancelar</Button>
                    <Button type="submit" :disabled="createForm.processing">Guardar Cita</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Modal Actualizar Estado (Reglas de Negocio / Auditoría) -->
    <Dialog :open="isStatusModalOpen" @update:open="isStatusModalOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="uppercase">Marcar como {{ targetStatus }}</DialogTitle>
                <DialogDescription>
                    Estás a punto de cambiar el estado de la cita de {{ selectedAppointment?.customer?.name }}.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="updateStatus" class="space-y-4">
                
                <!-- Justificación requerida para Cancelar o Reagendar -->
                <div v-if="targetStatus === 'cancelada' || targetStatus === 'reagendada'" class="space-y-2">
                    <Label class="text-red-500 font-bold">Justificación (Obligatoria para Auditoría)</Label>
                    <Input v-model="statusForm.justification" placeholder="Ej. El cliente llamó para cancelar" required minlength="5" />
                    <span class="text-xs text-muted-foreground">Esta acción será revisada por el Administrador.</span>
                    <span class="text-sm text-red-500" v-if="statusForm.errors.justification">{{ statusForm.errors.justification }}</span>
                </div>

                <!-- Aprobación del cliente requerida para Adelantar -->
                <div v-if="targetStatus === 'adelantada'" class="flex items-center space-x-2 mt-4">
                    <input type="checkbox" id="client_approved" v-model="statusForm.client_approved" class="w-4 h-4 rounded" required />
                    <Label for="client_approved">Confirmo que el cliente aprobó el adelanto de su cita.</Label>
                </div>

                <!-- Método de pago al Completar (genera la venta para las métricas) -->
                <div v-if="targetStatus === 'completada'" class="space-y-2">
                    <Label>Método de Pago</Label>
                    <Select v-model="statusForm.payment_method" required>
                        <SelectTrigger><SelectValue placeholder="Seleccione método de pago" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="efectivo">Efectivo</SelectItem>
                            <SelectItem value="transferencia">Transferencia</SelectItem>
                            <SelectItem value="tarjeta">Tarjeta</SelectItem>
                        </SelectContent>
                    </Select>
                    <span class="text-xs text-muted-foreground">Se registrará la venta por {{ formatPrice(selectedAppointment?.price) }} y el cliente acumulará puntos.</span>
                    <span class="text-sm text-red-500" v-if="statusForm.errors.payment_method">{{ statusForm.errors.payment_method }}</span>
                </div>

                <DialogFooter class="mt-4">
                    <Button type="button" variant="outline" @click="isStatusModalOpen = false">Cerrar</Button>
                    <Button type="submit" :disabled="statusForm.processing">Confirmar Cambio</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
