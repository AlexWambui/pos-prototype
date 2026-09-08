<script setup>
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import Button from '@/components/ui/button/Button.vue';
import TextInput from '@/components/ui/input/Input.vue';
import TextArea from '@/components/ui/textarea/Textarea.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useFormatters } from '@/composables/useFormatters';
import { computed, ref } from 'vue';
import shifts from '@/routes/shifts';
import { dashboard } from '@/routes';

const { formatNumber, formatDecimal, formatCurrency } = useFormatters();

const props = defineProps({
    shift: Object,
    expectedCash: Number,
    sales: {
        type: Array,
        default: () => []
    },
    cashPayments: {
        type: Number,
        default: 0
    }
});

const form = useForm({
    closing_cash: '',
    notes: ''
});

const showReconciliation = ref(false);

// Calculate expected cash breakdown
const expectedBreakdown = computed(() => {
    const cashSales = props.cashPayments || 0;
    return {
        openingCash: props.shift.opening_cash,
        cashSales: cashSales,
        total: props.shift.opening_cash + cashSales
    };
});

// Calculate variance
const variance = computed(() => {
    if (!form.closing_cash) return 0;
    return parseFloat(form.closing_cash) - expectedBreakdown.value.total;
});

const varianceClass = computed(() => {
    if (variance.value > 0) return 'text-green-600';
    if (variance.value < 0) return 'text-red-600';
    return 'text-gray-600';
});

const submit = () => {
    form.put(shifts.update().url);
};

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString();
};
</script>

<template>
    <Head title="Close Shift" />
    
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Close Shift</h2>
                        <div class="text-sm text-gray-500">
                            Shift opened: {{ formatDate(shift.opened_at) }}
                        </div>
                    </div>

                    <!-- Shift Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Shift Duration</div>
                            <div class="text-lg font-semibold">
                                {{ Math.round((new Date() - new Date(shift.opened_at)) / 3600000 * 10) / 10 }} hours
                            </div>
                            <div class="text-xs text-gray-500">
                                Started at {{ formatTime(shift.opened_at) }}
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Total Sales</div>
                            <div class="text-lg font-semibold">{{ formatDecimal(expectedBreakdown.cashSales) }}</div>
                            <div class="text-xs text-gray-500">{{ sales.length }} transactions</div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Expected Cash</div>
                            <div class="text-lg font-semibold">{{ formatDecimal(expectedBreakdown.total) }}</div>
                            <div class="text-xs text-gray-500">
                                Opening: {{ formatDecimal(expectedBreakdown.openingCash) }} + Sales: {{ formatDecimal(expectedBreakdown.cashSales) }}
                            </div>
                        </div>
                    </div>

                    <!-- Sales List Toggle -->
                    <div class="mb-6">
                        <button 
                            @click="showReconciliation = !showReconciliation"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1"
                        >
                            <span>{{ showReconciliation ? 'Hide' : 'Show' }} transaction list</span>
                            <svg :class="{ 'rotate-180': showReconciliation }" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Sales List -->
                        <div v-if="showReconciliation" class="mt-4">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sale #</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cash</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="sale in sales" :key="sale.id">
                                        <td class="px-4 py-2 text-sm">{{ formatTime(sale.completed_at) }}</td>
                                        <td class="px-4 py-2 text-sm">{{ sale.sale_number }}</td>
                                        <td class="px-4 py-2 text-sm text-right">{{ formatNumber(sale.total_amount) }}</td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            {{ formatNumber(sale.cash_amount || 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Close Shift Form -->
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="mb-4">
                            <Label for="closing_cash" class="required">Actual Cash Count</Label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">KES</span>
                                </div>
                                <TextInput
                                    id="closing_cash"
                                    v-model="form.closing_cash"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="pl-12 block w-full"
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.closing_cash" class="mt-2" />
                        </div>

                        <!-- Variance Display -->
                        <div v-if="form.closing_cash" class="p-4 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Variance:</span>
                                <span :class="varianceClass" class="text-lg font-semibold">
                                    {{ variance > 0 ? '+' : '' }}{{ formatNumber(variance) }}
                                </span>
                            </div>
                            <p v-if="variance !== 0" class="text-sm text-gray-600 mt-1">
                                {{ variance > 0 ? 'Over' : 'Short' }} by {{ formatNumber(Math.abs(variance)) }}
                            </p>
                        </div>

                        <div class="mb-4">
                            <Label for="notes">Notes (Optional)</Label>
                            <TextArea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full"
                                placeholder="Add any notes about discrepancies or issues..."
                            />
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <Link
                                :href="dashboard()"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Back to POS
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-red-600 hover:bg-red-700"
                            >
                                {{ form.processing ? 'Closing Shift...' : 'Close Shift' }}
                            </Button>
                        </div>
                    </form>

                    <!-- Warning for large variances -->
                    <div v-if="variance && Math.abs(variance) > 100" class="mt-4 p-4 bg-yellow-50 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Large Variance Detected</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>The variance of {{ formatNumber(Math.abs(variance)) }} is significant. Please double-check your cash count and review the transactions before closing.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>