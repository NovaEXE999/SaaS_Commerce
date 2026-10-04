<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Pencil, Trash2, Scissors } from '@lucide/vue';
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
interface Service {
    id: number;
    name: string;
    description: string | null;
    price: string; // Decimal de MySQL viene como string
    appointments_count: number;
}

interface PaginationData {
    data: Service[];
    current_page: number;
    last_page: number;
    // ... otros metadatos
}

const props = defineProps<{
    services: PaginationData;
}>();

// Estado del Modal
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingServiceId = ref<number | null>(null);

// Formulario de Inertia
const form = useForm({
    name: '',
    description: '',
    price: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingServiceId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (service: Service) => {
    isEditing.value = true;
    editingServiceId.value = service.id;
    form.name = service.name;
    form.description = service.description || '';
    form.price = service.price;
    isModalOpen.value = true;
};

const saveService = () => {
    if (isEditing.value && editingServiceId.value) {
        form.put(`/services/${editingServiceId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post('/services', {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const deleteService = (service: Service) => {
    if (confirm(`¿Estás seguro de eliminar el servicio "${service.name}"? Las citas pasadas conservan su precio.`)) {
        useForm({}).delete(`/services/${service.id}`);
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Servicios', href: '/services' },
        ],
    },
});
</script>

<template>
    <Head title="Catálogo de Servicios" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">

        <div class="flex items-center justify-between space-y-2">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Servicios</h2>
                <p class="text-muted-foreground">Define los servicios y sus precios. Al completar una cita se genera la venta automáticamente.</p>
            </div>
            <Button @click="openCreateModal">
                <Plus class="mr-2 h-4 w-4" /> Nuevo Servicio
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Servicio</TableHead>
                            <TableHead>Descripción</TableHead>
                            <TableHead class="text-right">Precio</TableHead>
                            <TableHead class="text-right">Citas</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="services.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center">
                                <div class="flex flex-col items-center gap-2 py-4">
                                    <Scissors class="h-8 w-8 text-muted-foreground" />
                                    <span>No hay servicios registrados. Crea el primero para poder agendar citas con precio.</span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="service in services.data" :key="service.id">
                            <TableCell class="font-medium">
                                {{ service.name }}
                            </TableCell>
                            <TableCell class="text-muted-foreground truncate max-w-[200px]">
                                {{ service.description || 'Sin descripción' }}
                            </TableCell>
                            <TableCell class="text-right font-medium">
                                ${{ parseFloat(service.price).toLocaleString() }}
                            </TableCell>
                            <TableCell class="text-right text-muted-foreground">
                                {{ service.appointments_count }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" @click="openEditModal(service)">
                                        <Pencil class="h-4 w-4 text-blue-500" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteService(service)">
                                        <Trash2 class="h-4 w-4 text-red-500" />
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
                <DialogTitle>{{ isEditing ? 'Editar Servicio' : 'Nuevo Servicio' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditing ? 'Modifica los datos del servicio. Las citas pasadas conservan su precio.' : 'Ingresa los datos del nuevo servicio.' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="saveService" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" required placeholder="Ej. Corte Clásico" />
                    <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                </div>
                <div class="grid gap-2">
                    <Label for="description">Descripción</Label>
                    <Input id="description" v-model="form.description" placeholder="Ej. Corte con tijera o máquina + lavado" />
                    <span v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</span>
                </div>
                <div class="grid gap-2">
                    <Label for="price">Precio ($)</Label>
                    <Input id="price" type="number" step="0.01" min="0" v-model="form.price" required />
                    <span v-if="form.errors.price" class="text-sm text-red-500">{{ form.errors.price }}</span>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isModalOpen = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Guardar</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
