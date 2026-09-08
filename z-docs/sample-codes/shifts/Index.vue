<script setup>
import AuthenticatedLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useFormatters } from '@/composables/useFormatters';

const { formatNumber, formatDecimal, formatCurrency } = useFormatters();

defineProps({
    shifts: Object
});

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const calculateDuration = (opened, closed) => {
    const start = new Date(opened);
    const end = closed ? new Date(closed) : new Date();
    const hours = (end - start) / 3600000;
    return hours.toFixed(1) + 'h';
};
</script>

<template>
    <Head title="Shift History" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">Shift History</h2>
                            <Link :href="route('shifts.open')">
                                <Button>Open New Shift</Button>
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Opened
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Closed
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Duration
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Opening Cash
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sales
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Closing Cash
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Variance
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="shift in shifts.data" :key="shift.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ formatDate(shift.opened_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ shift.closed_at ? formatDate(shift.closed_at) : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ calculateDuration(shift.opened_at, shift.closed_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            {{ formatCurrency(shift.opening_cash) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            {{ formatCurrency(shift.total_sales || 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            {{ shift.closing_cash ? formatCurrency(shift.closing_cash) : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            <span v-if="shift.closing_cash" 
                                                  :class="shift.closing_cash - shift.opening_cash - (shift.total_sales || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ formatCurrency((shift.closing_cash - shift.opening_cash - (shift.total_sales || 0))) }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="!shift.closed_at" 
                                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Open
                                            </span>
                                            <span v-else 
                                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Closed
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            <Link v-for="link in shifts.links" :key="link.label" 
                                  :href="link.url || '#'" 
                                  v-html="link.label"
                                  :class="{
                                      'px-3 py-1 mx-1 rounded': true,
                                      'bg-blue-600 text-white': link.active,
                                      'bg-gray-200 text-gray-700': !link.active && link.url,
                                      'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url
                                  }">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>