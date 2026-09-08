<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import users from '@/routes/users';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FormError from '@/components/custom/FormError.vue';

interface Props {
    branches: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

// Role constants matching the enum
const ROLES = {
    SUPER_ADMIN: 0,
    ADMIN: 1,
    CASHIER: 2,
    SUPPLIER: 3,
    CUSTOMER: 4,
} as const;

const STATUS = {
    INACTIVE: 0,
    ACTIVE: 1,
    SUSPENDED: 2,
} as const;

const statusOptions = [
    { value: STATUS.INACTIVE, label: 'Inactive' },
    { value: STATUS.ACTIVE, label: 'Active' },
    { value: STATUS.SUSPENDED, label: 'Suspended' },
];

const roleOptions = [
    { value: ROLES.SUPER_ADMIN, label: 'Super Admin' },
    { value: ROLES.ADMIN, label: 'Admin' },
    { value: ROLES.CASHIER, label: 'Cashier' },
    { value: ROLES.SUPPLIER, label: 'Supplier' },
    { value: ROLES.CUSTOMER, label: 'Customer' },
];

const positionOptions = [
    { value: 'Cashier', label: 'Cashier' },
    { value: 'Manager', label: 'Manager' },
    { value: 'Team Leader', label: 'Team Leader' },
];

const paymentTermsOptions = [
    { value: 'net_30', label: 'Net 30' },
    { value: 'net_60', label: 'Net 60' },
    { value: 'net_90', label: 'Net 90' },
    { value: 'prepaid', label: 'Prepaid' },
];

const form = useForm({
    // User fields
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: null as number | null,
    status: STATUS.ACTIVE,

    // Staff fields (for cashier)
    position: '',
    hired_at: '',
    branch_id: null as number | null,

    // Customer fields
    loyalty_points: 0,
    credit_limit: null as number | null,

    // Supplier fields
    company_name: '',
    payment_terms: '',
    tax_id: '',
    is_active: true,
});

const handleSubmit = () => {
    form.post(users.store.url());
};

// Computed property to check if form is for cashier
const isCashier = () => form.role === ROLES.CASHIER;
const isCustomer = () => form.role === ROLES.CUSTOMER;
const isSupplier = () => form.role === ROLES.SUPPLIER;
</script>

<template>
    <Head title="Create User" />

    <div class="AppContainer">
        <div class="create_user_form">
            <form @submit.prevent="handleSubmit">
                <!-- Basic User Information -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h2 class="text-lg font-semibold mb-4">Basic Information</h2>
                    
                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="name" class="required">Name</Label>
                            <Input v-model="form.name" type="text" placeholder="Full name" />
                            <FormError :error="form.errors.name" />
                        </div>

                        <div class="inputs_group">
                            <Label for="email" class="required">Email Address</Label>
                            <Input v-model="form.email" type="email" placeholder="Email address" />
                            <FormError :error="form.errors.email" />
                        </div>
                    </div>

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="password" class="required">Password</Label>
                            <Input v-model="form.password" type="password" placeholder="Password" />
                            <FormError :error="form.errors.password" />
                        </div>

                        <div class="inputs_group">
                            <Label for="password_confirmation" class="required">Confirm Password</Label>
                            <Input v-model="form.password_confirmation" type="password" placeholder="Confirm password" />
                            <FormError :error="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="role" class="required">User Role</Label>
                            <Select v-model="form.role">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select user role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="option in roleOptions" 
                                            :key="option.value" 
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.role" />
                        </div>

                        <div class="inputs_group">
                            <Label for="status" class="required">Account Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select account status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="option in statusOptions" 
                                            :key="option.value" 
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.status" />
                        </div>
                    </div>
                </div>

                <!-- Cashier Specific Fields -->
                <div v-if="isCashier()" class="bg-white p-6 rounded-lg shadow mb-6">
                    <h2 class="text-lg font-semibold mb-4">Cashier Information</h2>
                    
                    <!-- <div class="inputs_group">
                        <Label for="staff_code" class="required">Staff Code</Label>
                        <Input v-model="form.staff_code" type="text" placeholder="e.g., STAFF001" />
                        <FormError :error="form.errors.staff_code" />
                    </div> -->

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="position" class="required">Position</Label>
                            <Select v-model="form.position">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select position" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="option in positionOptions" 
                                            :key="option.value" 
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.position" />
                        </div>

                        <div class="inputs_group">
                            <Label for="branch_id">Branch</Label>
                            <Select v-model="form.branch_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="branch in branches" 
                                            :key="branch.id" 
                                            :value="branch.id"
                                        >
                                            {{ branch.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.branch_id" />
                        </div>
                    </div>

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="hired_at">Hired Date</Label>
                            <Input v-model="form.hired_at" type="date" />
                            <FormError :error="form.errors.hired_at" />
                        </div>
                    </div>
                </div>

                <!-- Customer Specific Fields -->
                <div v-if="isCustomer()" class="bg-white p-6 rounded-lg shadow mb-6">
                    <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                    
                    <!-- <div class="inputs_group">
                        <Label for="customer_code">Customer Code</Label>
                        <Input v-model="form.customer_code" type="text" placeholder="e.g., CUST001" />
                        <FormError :error="form.errors.customer_code" />
                    </div> -->

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="branch_id">Home Branch</Label>
                            <Select v-model="form.branch_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select home branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="branch in branches" 
                                            :key="branch.id" 
                                            :value="branch.id"
                                        >
                                            {{ branch.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.branch_id" />
                        </div>

                        <div class="inputs_group">
                            <Label for="loyalty_points">Loyalty Points</Label>
                            <Input v-model="form.loyalty_points" type="number" placeholder="Initial loyalty points" />
                            <FormError :error="form.errors.loyalty_points" />
                        </div>
                    </div>

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="credit_limit">Credit Limit</Label>
                            <Input v-model="form.credit_limit" type="number" step="0.01" placeholder="Credit limit" />
                            <FormError :error="form.errors.credit_limit" />
                        </div>
                    </div>
                </div>

                <!-- Supplier Specific Fields -->
                <div v-if="isSupplier()" class="bg-white p-6 rounded-lg shadow mb-6">
                    <h2 class="text-lg font-semibold mb-4">Supplier Information</h2>
                    
                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="company_name" class="required">Company Name</Label>
                            <Input v-model="form.company_name" type="text" placeholder="Company name" />
                            <FormError :error="form.errors.company_name" />
                        </div>

                        <div class="inputs_group">
                            <Label for="payment_terms" class="required">Payment Terms</Label>
                            <Select v-model="form.payment_terms">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select payment terms" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="option in paymentTermsOptions" 
                                            :key="option.value" 
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.payment_terms" />
                        </div>
                    </div>

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <Label for="branch_id">Associated Branch</Label>
                            <Select v-model="form.branch_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem 
                                            v-for="branch in branches" 
                                            :key="branch.id" 
                                            :value="branch.id"
                                        >
                                            {{ branch.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormError :error="form.errors.branch_id" />
                        </div>

                        <div class="inputs_group">
                            <Label for="tax_id">Tax ID</Label>
                            <Input v-model="form.tax_id" type="text" placeholder="Tax identification number" />
                            <FormError :error="form.errors.tax_id" />
                        </div>
                    </div>

                    <div class="inputs_group_wrapper">
                        <div class="inputs_group">
                            <div class="flex items-center gap-3">
                                <Checkbox v-model="form.is_active" id="is_active" />
                                <Label for="is_active">Is Active?</Label>
                            </div>
                            <FormError :error="form.errors.is_active" />
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-start space-x-3 pt-4">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ form.processing ? 'Creating User...' : 'Create User' }}
                    </Button>

                    <Link :href="users.index().url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>