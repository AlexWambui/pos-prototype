<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import InputError from '@/components/InputError.vue';
import branches from '@/routes/branches';

interface BranchFormData {
    name: string;
    code: string;
    phone_number: string;
    email: string,
    address: string;
    city: string;
    is_active: boolean,
};

const form = useForm<BranchFormData>({
  name: '',
  code: '',
  phone_number: '',
  email: '',
  address: '',
  city: '',
  is_active: true,
});

const handleSubmit = () => {
    form.post(branches.store.url());
};
</script>

<template>
    <Head title="Create Branch" />

    <div class="form">
        <form @submit.prevent="handleSubmit">
            <div class="inputs-group">
                <Label for="name" class="required">Name</Label>
                <Input v-model="form.name" type="text" placeholder="Name of the branch" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="inputs-group">
                <Label for="code">Branch Code</Label>
                <Input v-model="form.code" type="text" placeholder="Branch Code (eg. BR-0002)" />
                <InputError :message="form.errors.code" />
            </div>

            <div class="inputs-group">
                <Label for="phone_number">Phone Number</Label>
                <Input v-model="form.phone_number" type="text" placeholder="Phone Number" />
                <InputError :message="form.errors.phone_number" />
            </div>

            <div class="inputs-group">
                <Label for="email">Email Address</Label>
                <Input v-model="form.email" type="text" placeholder="Email Address" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="inputs-group">
                <Label for="city">City</Label>
                <Input v-model="form.city" type="text" placeholder="City" />
                <InputError :message="form.errors.city" />
            </div>

            <div class="inputs-group">
                <Label for="address">Address</Label>
                <Input v-model="form.address" type="text" placeholder="Address" />
                <InputError :message="form.errors.address" />
            </div>

            <div class="inputs-group">
                <div class="flex items-center gap-3">
                    <Checkbox v-model="form.is_active" id="is_active" />
                    <Label for="is_active">Is Active?</Label>
                </div>
                <InputError :message="form.errors.is_active" />
            </div>

            <div class="flex justify-start space-x-3 pt-4">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    {{ form.processing ? 'Creating Branch...' : 'Create Branch' }}
                </Button>

                <Link :href="branches.index().url">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>