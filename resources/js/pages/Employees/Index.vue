<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { UserCheck, UserX, UserPlus, Users, Shield } from '@lucide/vue';

const props = defineProps<{
    employees: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
        is_active: boolean;
        deleted_at: string | null;
        created_at: string;
    }>;
}>();

const isCreateModalOpen = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'empleado',
});

const submitCreate = () => {
    form.post('/employees', {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
};

const disableEmployee = (id: number) => {
    if (confirm('¿Estás seguro de inhabilitar esta cuenta de empleado? El usuario perderá acceso de inmediato.')) {
        router.delete(`/employees/${id}`);
    }
};

const restoreEmployee = (id: number) => {
    router.put(`/employees/${id}/restore`);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        dateStyle: 'medium',
    });
};
</script>

<template>
    <Head title="Gestión de Personal" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                    <Users class="h-8 w-8 text-primary" />
                    Gestión de Personal y Cuentas
                </h1>
                <p class="text-muted-foreground mt-1">
                    Administra los accesos de empleados a tu negocio, crea cuentas e inhabilita usuarios.
                </p>
            </div>

            <!-- Botón / Modal Crear Empleado -->
            <Dialog v-model:open="isCreateModalOpen">
                <DialogTrigger as-child>
                    <Button class="gap-2">
                        <UserPlus class="h-4 w-4" />
                        Nuevo Empleado
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Registrar Nuevo Empleado</DialogTitle>
                        <DialogDescription>
                            Crea una cuenta para que un miembro del equipo acceda al sistema.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                        <div class="space-y-2">
                            <Label for="name">Nombre Completo</Label>
                            <Input id="name" v-model="form.name" required placeholder="Ej. Roberto Gómez" />
                            <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Correo Electrónico</Label>
                            <Input id="email" type="email" v-model="form.email" required placeholder="empleado@negocio.com" />
                            <span v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Contraseña Temporal</Label>
                            <Input id="password" type="password" v-model="form.password" required placeholder="Mínimo 8 caracteres" />
                            <span v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="role">Rol</Label>
                            <Select v-model="form.role">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona un rol" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="empleado">Empleado (Operativo)</SelectItem>
                                    <SelectItem value="admin">Administrador (Acceso total)</SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.role" class="text-xs text-red-500">{{ form.errors.role }}</span>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="isCreateModalOpen = false">Cancelar</Button>
                            <Button type="submit" :disabled="form.processing">Registrar Cuenta</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Tabla de Empleados -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Miembros del Equipo</CardTitle>
                <CardDescription>
                    Listado del personal autorizado para operar en este negocio.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="props.employees.length === 0" class="text-center py-10 text-muted-foreground">
                    <Users class="h-10 w-10 mx-auto mb-2 opacity-50" />
                    No hay otros empleados registrados en este negocio. Haz clic en "Nuevo Empleado" para agregar uno.
                </div>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Correo Electrónico</TableHead>
                            <TableHead>Rol</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Fecha Registro</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="emp in props.employees" :key="emp.id" :class="{ 'opacity-60 bg-muted/20': !emp.is_active }">
                            <TableCell class="font-medium">{{ emp.name }}</TableCell>
                            <TableCell>{{ emp.email }}</TableCell>
                            <TableCell>
                                <Badge :variant="emp.role === 'admin' ? 'default' : 'secondary'" class="capitalize flex w-fit items-center gap-1">
                                    <Shield v-if="emp.role === 'admin'" class="h-3 w-3" />
                                    {{ emp.role }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge v-if="emp.is_active" variant="outline" class="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300">
                                    Activo
                                </Badge>
                                <Badge v-else variant="destructive">
                                    Inhabilitado
                                </Badge>
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ formatDate(emp.created_at) }}</TableCell>
                            <TableCell class="text-right">
                                <Button 
                                    v-if="emp.is_active" 
                                    variant="outline" 
                                    size="sm" 
                                    class="text-red-600 hover:bg-red-50 hover:text-red-700 border-red-200 dark:hover:bg-red-950"
                                    @click="disableEmployee(emp.id)"
                                >
                                    <UserX class="h-3.5 w-3.5 mr-1" />
                                    Inhabilitar
                                </Button>
                                <Button 
                                    v-else 
                                    variant="outline" 
                                    size="sm" 
                                    class="text-green-600 hover:bg-green-50 hover:text-green-700 border-green-200 dark:hover:bg-green-950"
                                    @click="restoreEmployee(emp.id)"
                                >
                                    <UserCheck class="h-3.5 w-3.5 mr-1" />
                                    Reactivar
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
