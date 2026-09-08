<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/ui/label/Label.vue';
import Button from '@/components/ui/button/Button.vue';
import TextInput from '@/components/ui/input/Input.vue';
import { useFormatters } from '@/composables/useFormatters';
import shifts from '@/routes/shifts';
import { dashboard } from '@/routes';

const { formatNumber, formatDecimal, formatCurrency } = useFormatters();

const props = defineProps({
    lastShift: Object,
});

const form = useForm({
    opening_cash: '',
});

const submit = () => {
    form.post(shifts.store().url);
};

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

// Format currency for display
const formattedOpeningCash = computed({
    get: () => form.opening_cash,
    set: (value) => {
        // Remove non-numeric characters except decimal point
        const cleaned = value.toString().replace(/[^\d.]/g, '');
        form.opening_cash = cleaned;
    }
});
</script>

<template>
    <Head title="Open Shift" />
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-6">Open New Shift</h2>

                    <!-- Last Shift Info -->
                    <div v-if="lastShift" class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Previous Shift</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="text-gray-600">Opened:</div>
                            <div class="font-medium">{{ formatDate(lastShift.opened_at) }}</div>
                            
                            <div class="text-gray-600">Closed:</div>
                            <div class="font-medium">{{ formatDate(lastShift.closed_at) }}</div>
                            
                            <div class="text-gray-600">Opening Cash:</div>
                            <div class="font-medium">{{ formatDecimal(lastShift.opening_cash) }}</div>
                            
                            <div class="text-gray-600">Closing Cash:</div>
                            <div class="font-medium">{{ formatDecimal(lastShift.closing_cash) }}</div>
                            
                            <div v-if="lastShift.closing_cash" class="text-gray-600">Difference:</div>
                            <div v-if="lastShift.closing_cash" 
                                 :class="lastShift.closing_cash - lastShift.opening_cash >= 0 ? 'text-green-600' : 'text-red-600'"
                                 class="font-medium">
                                {{ formatDecimal(lastShift.closing_cash - lastShift.opening_cash) }}
                            </div>
                        </div>
                    </div>

                    <!-- Open Shift Form -->
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="opening_cash" value="Opening Cash Amount *" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">KES</span>
                                </div>
                                <TextInput
                                    id="opening_cash"
                                    v-model="formattedOpeningCash"
                                    type="text"
                                    class="pl-12 block w-full"
                                    placeholder="0.00"
                                    required
                                    autofocus
                                />
                            </div>
                            <InputError :message="form.errors.opening_cash" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Enter the amount of cash you have in the drawer at the start of your shift.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link
                                :href="dashboard()"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Cancel
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Opening Shift...' : 'Open Shift' }}
                            </Button>
                        </div>
                    </form>

                    <!-- Instructions -->
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-800 mb-2">Before you start:</h3>
                        <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                            <li>Count the cash in your drawer carefully</li>
                            <li>Enter the exact amount you have</li>
                            <li>You cannot process sales without an open shift</li>
                            <li>Only one shift can be open at a time</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>