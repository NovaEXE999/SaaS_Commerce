<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Pencil, Trash2, UserRound } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

// Tipos base
interface Customer {
    id: number;
    name: string;
    phone: string | null;
    points: number;
    appointments_count: number;
}

interface PaginationData {
    data: Customer[];
    current_page: number;
    last_page: number;
    // ... otros metadatos
}

const props = defineProps<{
    customers: PaginationData;
}>();

// Estado del Modal
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingCustomerId = ref<number | null>(null);

// Formulario de Inertia
const form = useForm({
    name: '',
    phone: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingCustomerId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (customer: Customer) => {
    isEditing.value = true;
    editingCustomerId.value = customer.id;
    form.name = customer.name;
    form.phone = customer.phone || '';
    isModalOpen.value = true;
};

const saveCustomer = () => {
    if (isEditing.value && editingCustomerId.value) {
        form.put(`/customers/${editingCustomerId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post('/customers', {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const deleteCustomer = (customer: Customer) => {
    if (customer.appointments_count > 0) {
        alert('No se puede eliminar el cliente porque tiene citas registradas.');
        return;
    }
    if (confirm(`¿Estás seguro de eliminar a ${customer.name}?`)) {
        useForm({}).delete(`/customers/${customer.id}`);
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Clientes', href: '/customers' },
        ],
    },
});
</script>

<template>
    <Head title="Gestión de Clientes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">

        <div class="flex items-center justify-between space-y-2">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Clientes</h2>
                <p class="text-muted-foreground">Registra y gestiona los clientes de tu negocio.</p>
            </div>
            <Button @click="openCreateModal">
                <Plus class="mr-2 h-4 w-4" /> Nuevo Cliente
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Teléfono</TableHead>
                            <TableHead class="text-right">Puntos</TableHead>
                            <TableHead class="text-right">Citas</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="customers.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center">
                                <div class="flex flex-col items-center gap-2 py-4">
                                    <UserRound class="h-8 w-8 text-muted-foreground" />
                                    <span>No hay clientes registrados. Crea el primero para poder agendar citas.</span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="customer in customers.data" :key="customer.id">
                            <TableCell class="font-medium">
                                {{ customer.name }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ customer.phone || 'Sin teléfono' }}
                            </TableCell>
                            <TableCell class="text-right">
                                <span class="font-semibold text-primary">{{ customer.points }} pts</span>
                            </TableCell>
                            <TableCell class="text-right text-muted-foreground">
                                {{ customer.appointments_count }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" @click="openEditModal(customer)">
                                        <Pencil class="h-4 w-4 text-blue-500" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="deleteCustomer(customer)"
                                        :disabled="customer.appointments_count > 0"
                                        :title="customer.appointments_count > 0 ? 'Tiene citas registradas' : 'Eliminar cliente'"
                                    >
                                        <Trash2 class="h-4 w-4" :class="customer.appointments_count > 0 ? 'text-muted-foreground' : 'text-red-500'" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

    </div>

    <!-- Modal Crear/Editar -->
    <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditing ? 'Modifica los datos del cliente.' : 'Ingresa los datos del nuevo cliente.' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="saveCustomer" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" required placeholder="Ej. Juan Pérez" />
                    <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                </div>
                <div class="grid gap-2">
                    <Label for="phone">Teléfono (opcional)</Label>
                    <Input id="phone" v-model="form.phone" placeholder="Ej. 3001234567" />
                    <span v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</span>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isModalOpen = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Guardar</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
