<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { usePriceFormatter } from '@/composables/usePriceFormatter';
import orderRoutes from '@/routes/orders';
import type { Product } from '@/types/product';

const { formatPrice } = usePriceFormatter();

defineProps<{
    products: { data: Product[] };
    orderStatuses: Record<string, string>;
    deliveryStatuses: Record<string, string>;
}>();

// --- STATE ---
const cart = ref<Product[]>([]);
const deliveryMethod = ref<'shop' | 'delivery'>('shop');
const selectedArea = ref('');
const deliveryCost = ref(0);

// --- INERTIA FORM ---
const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    delivery_method: 'shop',
    order_channel: 'pos',
    location: '',
    area: '',
    address: '',
    delivery_cost: 0,
    amount_paid: 0,
    payments: [{ amount: 0, method: 'mpesa' }],
    cart_items: [] as Product[], 
});

// --- COMPUTED TOTALS ---
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.price, 0);
});

const total = computed(() => {
    return subtotal.value + deliveryCost.value;
});

// --- WATCHERS ---
// Update the form delivery_cost whenever deliveryCost changes
watch(deliveryCost, (newVal) => {
    form.delivery_cost = newVal;
});

// Watch for Area changes (Mock DB fetch)
watch(selectedArea, async (newArea) => {
    if (!newArea) {
        deliveryCost.value = 0;

        return;
    }

    // In a real scenario, you'd fetch this from your backend
    const mockCosts: Record<string, number> = {
        'Kilimani': 250,
        'Lavington': 300,
        'Westlands': 350
    };
    deliveryCost.value = mockCosts[newArea] || 150;
});

// Watch deliveryMethod to clear delivery fields if switched to shop
watch(deliveryMethod, (newMethod) => {
    form.delivery_method = newMethod;
    
    if (newMethod === 'shop') {
        form.location = '';
        form.area = '';
        form.address = '';
        selectedArea.value = '';
        deliveryCost.value = 0;
    }
});

// --- ACTIONS ---
const addToCart = (product: Product) => {
    // Check if already in cart
    const existing = cart.value.findIndex(item => item.id === product.id);

    if (existing !== -1) {
        // If you want to allow quantity increase, handle it here
        return; 
    }

    cart.value.push(product);
};

const removeFromCart = (id: number) => {
    cart.value = cart.value.filter(item => item.id !== id);
};

const payments = ref([
    { amount: 0, method: 'mpesa'}
]);

const addPaymentRow = () => {
    payments.value.push({amount: 0, method: 'mpesa'});
}

const removePaymentRow = (index: number) => {
    payments.value.splice(index, 1);
}

// const totalPaid = computed(() => {
//     return payments.value.reduce((sum, p) => sum + Number(p.amount), 0);
// });

const isAnonymous = ref(false);

watch(isAnonymous, (val) => {
    if (val) {
        form.customer_name = 'Guest';
        form.customer_phone = 'na';
        form.customer_email = 'guest@gmail.com';
    } else {
        form.customer_name = '';
        form.customer_phone = '';
        form.customer_email = '';
    }
});

const submitOrder = () => {
    // Populate the cart_items in the form before submitting
    form.cart_items = cart.value;

    form.payments = payments.value;

    form.post(orderRoutes.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset state after success
            cart.value = [];
            deliveryMethod.value = 'shop';
            deliveryCost.value = 0;
            selectedArea.value = '';
            payments.value = [{amount: 0, method: 'mpesa'}];
            form.reset(); 
        }
    });
};
</script>

<template>
    <Head title="POS - New Order" />

    <div class="pos-wrapper grid lg:grid-cols-2 gap-8 h-[84dvh] p-4 bg-background text-foreground overflow-hidden">
        <div class="products-wrapper overflow-y-auto pr-4">
            <h2 class="text-xl font-bold mb-4">Select Products</h2>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="product in products.data" :key="product.id" class="bg-background text-foreground p-3 rounded-lg shadow-sm border hover:shadow-md transition">
                    <div class="w-full h-32 overflow-hidden rounded mb-2 bg-gray-100">
                        <img :src="product.thumbnail_url" :alt="product.name" class="w-full h-full object-cover" />
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-sm truncate">{{ product.name }}</p>
                        <p class="text-blue-600 font-bold text-sm">{{ formatPrice(product.price) }}</p>
                        <Button 
                            type="button" 
                            size="sm"
                            class="w-full bg-blue-800 hover:bg-blue-900 text-white"
                            @click="addToCart(product)"
                        >
                            Add to Cart
                        </Button>
                    </div>
                </div>

                <div v-if="!products.data || products.data.length === 0" class="col-span-full text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📦</div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No products available!</h3>
                    <p class="text-gray-400">There's not stocked products!</p>
                </div>
            </div>
        </div>

        <div class="cart-wrapper bg-background text-foreground p-6 rounded-lg shadow-lg h-full overflow-y-auto flex flex-col">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">Current Order</h2>
            
            <!-- Cart Items List -->
            <div class="cart-items space-y-2 min-h-25 max-h-50 overflow-y-auto mb-4 flex-1" v-if="cart.length > 0">
                <div v-for="item in cart" :key="item.id" class="flex justify-between items-center text-sm bg-background text-foreground p-2 rounded border">
                    <div class="flex-1">
                        <span class="font-medium">{{ item.name }}</span>
                        <span class="text-gray-500 ml-2">x1</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-bold">{{ formatPrice(item.price) }}</span>
                        <Button 
                            type="button" 
                            variant="destructive" 
                            size="icon" 
                            class="h-6 w-6 rounded-full text-xs"
                            @click="removeFromCart(item.id)"
                        >
                            ✕
                        </Button>
                    </div>
                </div>
            </div>
            <div v-else class="text-gray-400 text-center py-6 flex-1">No items in cart</div>

            <!-- Order Form -->
            <form @submit.prevent="submitOrder" class="space-y-4 border-t pt-4">

                <div class="grid grid-cols-2 gap-3 items-start">
                    <div>
                        <Label for="order_channel" class="text-xs">Order Channel</Label>
                        <Select v-model="form.order_channel">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Channel" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="pos">POS</SelectItem>
                                    <SelectItem value="walk_in">Walk In</SelectItem>
                                    <SelectItem value="whatsapp">WhatsApp</SelectItem>
                                    <SelectItem value="tiktok">TikTok</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.order_channel" />
                    </div>

                    <div class="flex items-center gap-2 mb-2">
                        <input type="checkbox" id="isAnonymous" v-model="isAnonymous" class="w-4 h-4 rounded" />
                        <Label for="isAnonymous" class="text-sm cursor-pointer">Walk-in Customer (Skip Details)</Label>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div class="space-y-3">
                    <h3 class="font-semibold text-sm text-gray-700">Customer Information</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 sm:col-span-1">
                            <Label for="customer_name" class="text-xs">Name</Label>
                            <Input id="customer_name" v-model="form.customer_name" required />
                            <InputError :message="form.errors.customer_name" />
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <Label for="customer_phone" class="text-xs">Phone</Label>
                            <Input id="customer_phone" v-model="form.customer_phone" required />
                            <InputError :message="form.errors.customer_phone" />
                        </div>
                        <div class="col-span-2">
                            <Label for="customer_email" class="text-xs">Email (Optional)</Label>
                            <Input id="customer_email" v-model="form.customer_email" type="email" />
                            <InputError :message="form.errors.customer_email" />
                        </div>
                    </div>
                </div>

                <!-- Delivery Method -->
                <div class="space-y-2">
                    <h3 class="font-semibold text-sm text-gray-700">Delivery Method</h3>
                    <div class="flex gap-4">
                        <Label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" value="shop" v-model="deliveryMethod" /> Shop Pickup
                        </Label>
                        <Label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" value="delivery" v-model="deliveryMethod" /> Delivery
                        </Label>
                    </div>
                    <InputError :message="form.errors.delivery_method" />
                </div>

                <!-- Delivery Details (Conditional) -->
                <div v-if="deliveryMethod === 'delivery'" class="bg-gray-50 p-3 rounded border space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label for="location" class="text-xs">Location</Label>
                            <Input id="location" v-model="form.location" />
                            <InputError :message="form.errors.location" />
                        </div>
                        <div>
                            <Label for="area" class="text-xs">Area (Fetches Cost)</Label>
                            <Select v-model="selectedArea">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Area" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="Kilimani">Kilimani</SelectItem>
                                        <SelectItem value="Lavington">Lavington</SelectItem>
                                        <SelectItem value="Westlands">Westlands</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.area" />
                        </div>
                    </div>
                    <div>
                        <Label for="address" class="text-xs">Address</Label>
                        <Input id="address" v-model="form.address" />
                        <InputError :message="form.errors.address" />
                    </div>
                </div>

                <!-- Dynamic Payments -->
                <div class="space-y-2 mt-2">
                    <h3 class="font-semibold text-sm text-gray-700">Payments</h3>
                    <div v-for="(payment, index) in payments" :key="index" class="flex gap-2">
                        <Input 
                            v-model="payment.amount" 
                            type="number" 
                            step="0.01" 
                            placeholder="Amount" 
                            class="w-1/2" 
                        />
                        <Select v-model="payment.method">
                            <SelectTrigger class="w-1/2">
                                <SelectValue placeholder="Method" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="mpesa">M-Pesa</SelectItem>
                                    <SelectItem value="cash">Cash</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <Button type="button" variant="destructive" size="icon" class="h-9 w-9" @click="removePaymentRow(index)" v-if="payments.length > 1">
                            ✕
                        </Button>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addPaymentRow">
                        + Add Payment Method
                    </Button>
                </div>

                <!-- Summary & Payment -->
                <div class="bg-background text-foreground p-3 rounded space-y-1">
                    <div class="flex justify-between text-sm"><span>Subtotal</span><span>{{ formatPrice(subtotal) }}</span></div>
                    <div class="flex justify-between text-sm"><span>Delivery</span><span>{{ formatPrice(deliveryCost) }}</span></div>
                    <div class="flex justify-between font-bold text-base border-t pt-1 mt-1"><span>Total</span><span>{{ formatPrice(total) }}</span></div>

                    <!-- Show what statuses will be applied -->
                    <div class="mt-2 pt-2 border-t text-xs text-gray-500">
                        <p>Order will be: <span class="font-medium">{{ deliveryMethod === 'shop' ? 'Ready for Pickup' : 'Pending' }}</span></p>
                        <p>Delivery: <span class="font-medium">Pending</span></p>
                    </div>
                </div>

                <Button 
                    type="submit" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 mt-2"
                    :disabled="form.processing || cart.length === 0"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    Confirm Order
                </Button>
            </form>
        </div>
    </div>
</template>