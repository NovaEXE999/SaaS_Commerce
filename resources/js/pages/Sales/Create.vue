<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Trash2, ShoppingCart, ImagePlus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Product {
    id: number;
    name: string;
    price: string;
    stock: number;
}

interface Customer {
    id: number;
    name: string;
    points: number;
}

const props = defineProps<{
    products: Product[];
    customers: Customer[];
}>();

// Estado del Carrito
const cart = ref<{ product: Product; quantity: number }[]>([]);

const addToCart = (product: Product) => {
    const existing = cart.value.find(item => item.product.id === product.id);
    if (existing) {
        if (existing.quantity < product.stock) {
            existing.quantity++;
        }
    } else {
        cart.value.push({ product, quantity: 1 });
    }
};

const removeFromCart = (index: number) => {
    cart.value.splice(index, 1);
};

const totalAmount = computed(() => {
    return cart.value.reduce((total, item) => total + (parseFloat(item.product.price) * item.quantity), 0);
});

// Formulario de Venta
const form = useForm({
    customer_id: null as number | null,
    payment_method: 'efectivo',
    payment_proof: null as File | null,
    products: [] as { id: number; quantity: number }[],
    points_to_redeem: 0 as number,
});

const selectedCustomer = computed(() => {
    return props.customers.find(c => c.id === form.customer_id) ?? null;
});

const maxRedeemablePoints = computed(() => {
    if (!selectedCustomer.value) {
        return 0;
    }
    const byBalance = selectedCustomer.value.points;
    const byTotal = Math.floor(totalAmount.value / 1000);
    return Math.max(0, Math.min(byBalance, byTotal));
});

const discountAmount = computed(() => {
    return Math.min(form.points_to_redeem ?? 0, maxRedeemablePoints.value) * 1000;
});

const finalAmount = computed(() => {
    return Math.max(0, totalAmount.value - discountAmount.value);
});

watch(() => form.customer_id, () => {
    form.points_to_redeem = 0;
    if (form.errors.points_to_redeem) {
        form.clearErrors('points_to_redeem');
    }
});

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.payment_proof = target.files[0];
    }
};

const submitSale = () => {
    if (cart.value.length === 0) {
        alert('El carrito está vacío.');
        return;
    }

    // Mapear el carrito al formato que espera el backend
    form.products = cart.value.map(item => ({
        id: item.product.id,
        quantity: item.quantity,
    }));

    // Normalizar canje: sin cliente no hay canje
    if (!form.customer_id) {
        form.points_to_redeem = 0;
    } else {
        form.points_to_redeem = Math.max(0, Math.min(form.points_to_redeem ?? 0, maxRedeemablePoints.value));
    }

    form.post('/sales', {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            form.reset();
        },
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Punto de Venta', href: '/sales/create' },
        ],
    },
});
</script>

<template>
    <Head title="Punto de Venta (Caja)" />

    <div class="flex h-full flex-1 flex-col lg:flex-row gap-6 p-4 lg:p-8">
        
        <!-- Columna Izquierda: Listado de Productos -->
        <div class="flex-1 flex flex-col gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Catálogo</h2>
                <p class="text-muted-foreground">Selecciona los productos para agregar a la venta.</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                <Card 
                    v-for="product in products" 
                    :key="product.id"
                    class="cursor-pointer hover:border-primary transition-colors flex flex-col justify-between"
                    @click="addToCart(product)"
                >
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm">{{ product.name }}</CardTitle>
                        <CardDescription>Stock: {{ product.stock }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="font-bold text-lg">${{ parseFloat(product.price).toLocaleString() }}</div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Columna Derecha: Carrito y Checkout -->
        <div class="w-full lg:w-[400px] flex flex-col gap-4">
            <Card class="flex-1 flex flex-col">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShoppingCart class="h-5 w-5" />
                        Carrito de Venta
                    </CardTitle>
                </CardHeader>
                <CardContent class="flex-1 overflow-y-auto">
                    <div v-if="cart.length === 0" class="text-center text-muted-foreground py-10">
                        No hay productos en el carrito
                    </div>
                    <ul v-else class="space-y-4">
                        <li v-for="(item, index) in cart" :key="index" class="flex justify-between items-center border-b pb-2">
                            <div>
                                <p class="text-sm font-medium">{{ item.product.name }}</p>
                                <p class="text-xs text-muted-foreground">${{ parseFloat(item.product.price).toLocaleString() }} x {{ item.quantity }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-sm">${{ (parseFloat(item.product.price) * item.quantity).toLocaleString() }}</span>
                                <Button variant="ghost" size="icon" class="h-6 w-6 text-red-500" @click="removeFromCart(index)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </li>
                    </ul>
                </CardContent>
                
                <div class="p-6 border-t bg-muted/30">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-lg font-medium">Subtotal:</span>
                        <span class="text-xl font-bold">${{ totalAmount.toLocaleString() }}</span>
                    </div>
                    <div v-if="discountAmount > 0" class="flex justify-between items-center mb-2 text-green-600">
                        <span class="text-sm font-medium">Descuento por puntos ({{ Math.min(form.points_to_redeem ?? 0, maxRedeemablePoints) }} pts):</span>
                        <span class="text-sm font-bold">-${{ discountAmount.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-lg font-medium">Total:</span>
                        <span class="text-2xl font-bold">${{ finalAmount.toLocaleString() }}</span>
                    </div>

                    <form @submit.prevent="submitSale" class="space-y-4">
                        <div class="space-y-2">
                            <Label>Cliente (Para puntos)</Label>
                            <Select v-model="form.customer_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar Cliente (Opcional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Consumidor Final</SelectItem>
                                    <SelectItem v-for="c in customers" :key="c.id" :value="c.id">
                                        {{ c.name }} ({{ c.points }} pts)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="selectedCustomer && selectedCustomer.points > 0" class="space-y-2 rounded-md border p-3">
                            <Label :for="'points_to_redeem'">Canjear puntos (1 pt = $1.000)</Label>
                            <Input
                                id="points_to_redeem"
                                type="number"
                                min="0"
                                :max="maxRedeemablePoints"
                                v-model.number="form.points_to_redeem"
                            />
                            <p class="text-xs text-muted-foreground">
                                Disponible: {{ selectedCustomer.points }} pts · Máximo canjeable en esta compra: {{ maxRedeemablePoints }} pts.
                            </p>
                            <span v-if="form.errors.points_to_redeem" class="text-xs text-red-500">{{ form.errors.points_to_redeem }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label>Método de Pago</Label>
                            <Select v-model="form.payment_method" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione método" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="efectivo">Efectivo</SelectItem>
                                    <SelectItem value="transferencia">Transferencia</SelectItem>
                                    <SelectItem value="tarjeta">Tarjeta</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="form.payment_method === 'transferencia'" class="space-y-2">
                            <Label>Comprobante de Pago</Label>
                            <div class="flex items-center gap-2">
                                <Input type="file" accept="image/*" @change="handleFileUpload" required class="cursor-pointer" />
                            </div>
                            <p class="text-xs text-muted-foreground">Obligatorio subir pantallazo de la transferencia.</p>
                            <span v-if="form.errors.payment_proof" class="text-xs text-red-500">{{ form.errors.payment_proof }}</span>
                        </div>

                        <span v-if="(form.errors as any).error" class="text-sm text-red-500 block">{{ (form.errors as any).error }}</span>

                        <Button type="submit" class="w-full" size="lg" :disabled="form.processing || cart.length === 0">
                            Procesar Venta
                        </Button>
                    </form>
                </div>
            </Card>
        </div>
    </div>
</template>
