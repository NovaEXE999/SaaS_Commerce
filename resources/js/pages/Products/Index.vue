<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Pencil, Trash2, PackageOpen } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
} from '@/components/ui/dialog';

// Tipos base
interface Product {
    id: number;
    name: string;
    description: string | null;
    price: string; // Decimal de MySQL viene como string
    stock: number;
    min_stock: number;
}

interface PaginationData {
    data: Product[];
    current_page: number;
    last_page: number;
    // ... otros metadatos
}

const props = defineProps<{
    products: PaginationData;
}>();

// Estado del Modal
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingProductId = ref<number | null>(null);

// Formulario de Inertia
const form = useForm({
    name: '',
    description: '',
    price: '',
    stock: 0,
    min_stock: 0,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingProductId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (product: Product) => {
    isEditing.value = true;
    editingProductId.value = product.id;
    form.name = product.name;
    form.description = product.description || '';
    form.price = product.price;
    form.stock = product.stock;
    form.min_stock = product.min_stock ?? 0;
    isModalOpen.value = true;
};

const isLowStock = (product: Product) => {
    const threshold = (product.min_stock ?? 0) > 0 ? product.min_stock : 5;
    return product.stock <= threshold;
};

const saveProduct = () => {
    if (isEditing.value && editingProductId.value) {
        form.put(`/products/${editingProductId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post('/products', {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const deleteProduct = (id: number) => {
    if (confirm('¿Estás seguro de inhabilitar este producto?')) {
        useForm({}).delete(`/products/${id}`);
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Inventario', href: '/products' },
        ],
    },
});
</script>

<template>
    <Head title="Inventario de Productos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
        
        <div class="flex items-center justify-between space-y-2">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Inventario</h2>
                <p class="text-muted-foreground">Gestiona los productos de tu negocio.</p>
            </div>
            <Button @click="openCreateModal">
                <Plus class="mr-2 h-4 w-4" /> Nuevo Producto
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Descripción</TableHead>
                            <TableHead class="text-right">Precio</TableHead>
                            <TableHead class="text-right">Stock</TableHead>
                            <TableHead class="text-right">Stock Mín.</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="products.data.length === 0">
                            <TableCell colspan="6" class="h-24 text-center">
                                No hay productos registrados.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="product in products.data" :key="product.id">
                            <TableCell class="font-medium">
                                {{ product.name }}
                            </TableCell>
                            <TableCell class="text-muted-foreground truncate max-w-[200px]">
                                {{ product.description || 'Sin descripción' }}
                            </TableCell>
                            <TableCell class="text-right font-medium">
                                ${{ parseFloat(product.price).toLocaleString() }}
                            </TableCell>
                            <TableCell class="text-right">
                                <span :class="{'text-red-500 font-bold': isLowStock(product)}">
                                    {{ product.stock }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right text-muted-foreground">
                                {{ product.min_stock ?? 0 }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" @click="openEditModal(product)">
                                        <Pencil class="h-4 w-4 text-blue-500" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteProduct(product.id)">
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
                <DialogTitle>{{ isEditing ? 'Editar Producto' : 'Nuevo Producto' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditing ? 'Modifica los detalles del producto.' : 'Ingresa los datos del nuevo producto.' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="saveProduct" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" required />
                    <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                </div>
                <div class="grid gap-2">
                    <Label for="description">Descripción</Label>
                    <Input id="description" v-model="form.description" />
                    <span v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="price">Precio ($)</Label>
                        <Input id="price" type="number" step="0.01" min="0" v-model="form.price" required />
                        <span v-if="form.errors.price" class="text-sm text-red-500">{{ form.errors.price }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="stock">Stock Inicial</Label>
                        <Input id="stock" type="number" min="0" v-model="form.stock" required />
                        <span v-if="form.errors.stock" class="text-sm text-red-500">{{ form.errors.stock }}</span>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="min_stock">Stock mínimo (alerta)</Label>
                    <Input id="min_stock" type="number" min="0" v-model="form.min_stock" />
                    <p class="text-xs text-muted-foreground">Cuando el stock llegue a este nivel verás una alerta en el Dashboard. Usa 0 para el valor por defecto (5).</p>
                    <span v-if="form.errors.min_stock" class="text-sm text-red-500">{{ form.errors.min_stock }}</span>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isModalOpen = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Guardar</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
