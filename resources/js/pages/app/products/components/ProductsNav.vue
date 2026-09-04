<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import productCategoryRoutes from '@/routes/product-categories';
import productRoutes from '@/routes/products';

interface Props {
    currentPage: 'products' | 'discounts' | 'inventory' | 'product-categories';
}

defineProps<Props>();

const links = [
    { name: 'Products', href: productRoutes.index().url, key: 'products' },
    { name: 'Categories', href: productCategoryRoutes.index(), key: 'product-categories' },
];
</script>

<template>
    <div class="shop-nav pb-4 w-full border-b border-sidebar-border/80" aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-sm">
            <li v-for="(item, idx) in links" :key="item.key" class="flex items-center gap-2">
                <Link
                    :href="item.href"
                    class="transition-colors"
                    :class="{
                        'text-foreground font-semibold pointer-events-none': currentPage === item.key,
                        'text-muted-foreground hover:text-foreground' : currentPage !== item.key
                    }"
                >
                    {{ item.name }}
                </Link>
                <span v-if="idx < links.length - 1" class="text-muted-foreground">/</span>
            </li>
        </ol>
    </div>
</template>
