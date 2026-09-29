<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    order: {
        uuid: string;
        order_number: string;
        total_selling_price: number;
        customer_name: string;
    };
}>();
</script>

<template>
    <Head title="Order placed" />

    <div class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="text-2xl font-semibold">Thank you, {{ order.customer_name }}!</h1>

        <p class="mt-4 text-muted-foreground">
            Your order <strong>{{ order.order_number }}</strong> has been received.
        </p>

        <p class="mt-2 text-muted-foreground">
            Total: <strong>KES {{ order.total_selling_price }}</strong>
        </p>

        <p class="mt-6 text-sm text-muted-foreground">
            We'll be in touch shortly to confirm delivery details.
        </p>
    </div>
</template>