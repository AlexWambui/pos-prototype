<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref, computed } from 'vue';
import AppPageHeader from '@/components/custom/AppPageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Pagination from '@/components/custom/Pagination.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePriceFormatter } from '@/composables/usePriceFormatter';
import orderRoutes from '@/routes/orders';

const { formatPrice } = usePriceFormatter();

interface Order {
    id: number;
    uuid: string;
    order_number: string;
    customer_name: string;
    customer_phone: string;
    delivery_address: string;
    total_selling_price: number;
    amount_paid: number;
    payment_status: string;
    order_status: string;
    order_status_label: string;
    delivery_status: string;
    delivery_status_label: string;
}

interface Props {
    orders: {
        data: Order[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        };
    };
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const handleSearch = (value: string) => {
    router.get(orderRoutes.index().url, {
        search: value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const getDisplayRange = computed(() => {
    const { current_page, per_page, total } = props.orders.meta;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);

    return { start, end, total };
});

const hasActiveFilters = computed(() => !!search.value);

// Helper to get status color classes
const getOrderStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'pending': 'text-yellow-600',
        'confirmed': 'text-blue-600',
        'processing': 'text-indigo-600',
        'ready_for_pickup': 'text-purple-600',
        'completed': 'text-green-600',
        'cancelled': 'text-red-600',
        'refunded': 'text-gray-600',
    };

    return colors[status] || 'text-gray-600';
};

const getDeliveryStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'pending': 'text-yellow-600',
        'picked_up': 'text-green-600',
        'in_transit': 'text-indigo-600',
        'out_for_delivery': 'text-purple-600',
        'delivered': 'text-green-600',
        'delivery_failed': 'text-red-600',
        'returned': 'text-gray-600',
    };

    return colors[status] || 'text-gray-600';
};

const getPaymentStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'paid': 'text-green-600',
        'pending': 'text-yellow-600',
        'partially_paid': 'text-blue-600',
        'failed': 'text-red-600',
    };

    return colors[status] || 'text-gray-600';
};
</script>

<template>
    <Head title="Orders" />

    <AppPageHeader
        resourceName="Orders"
        v-model="search"
        search-placeholder="Search by order number or customer phone number..."
        :create-url="orderRoutes.create().url"
        create-label="Order"
        @search="handleSearch"
    />

    <div class="table-wrapper">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="id">#</TableHead>
                    <TableHead>Order</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Phone Number</TableHead>
                    <TableHead>Address</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Amount Paid</TableHead>
                    <TableHead>Payment</TableHead>
                    <TableHead>Order</TableHead>
                    <TableHead>Delivery</TableHead>
                    <TableHead class="actions">Actions</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow v-for="(order, index) in orders.data" :key="order.id">
                    <TableCell class="id">{{ (orders.meta.current_page - 1) * orders.meta.per_page + index + 1 }}</TableCell>
                    <TableCell>{{ order.order_number }}</TableCell>
                    <TableCell>{{ order.customer_name }}</TableCell>
                    <TableCell>{{ order.customer_phone }}</TableCell>
                    <TableCell>{{ order.delivery_address }}</TableCell>
                    <TableCell>{{ formatPrice(order.total_selling_price) }}</TableCell>
                    <TableCell>{{ formatPrice(order.amount_paid) }}</TableCell>
                    <TableCell :class="getPaymentStatusColor(order.payment_status)">
                        {{ order.payment_status }}
                    </TableCell>
                    <TableCell :class="getOrderStatusColor(order.order_status)">
                        {{ order.order_status_label }}
                    </TableCell>
                    <TableCell :class="getDeliveryStatusColor(order.delivery_status)">
                        {{ order.delivery_status_label || 'N/A' }}
                    </TableCell>
                    <TableCell class="actions w-20">
                        <div class="actions-wrapper">
                            <Link :href="orderRoutes.edit(order.uuid).url" class="action edit">
                                <Pencil />
                            </Link>
                            <span class="divider">|</span>
                            <DeleteConfirmationDialog 
                                :url="orderRoutes.destroy(order.uuid).url" 
                                title="Delete Product?" 
                                description="This product will be deleted permanently!" 
                                confirm-text="Delete Product"
                            >
                                <template #trigger>
                                    <button class="action delete">
                                        <Trash2 />
                                    </button>
                                </template>
                            </DeleteConfirmationDialog>
                        </div>
                    </TableCell>
                </TableRow>

                <TableRow v-if="orders.data.length === 0">
                    <TableCell colspan="9" class="blank-table-row">
                        No orders found.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <Pagination :meta="orders.meta" />

    <div class="table-results-summary">
        <p>
            Showing {{ getDisplayRange.start }} to {{ getDisplayRange.end }}
            of {{ getDisplayRange.total }} orders
        </p>
        <p v-if="hasActiveFilters" class="filtered-results">
            Filtered results
        </p>
    </div>
</template>
