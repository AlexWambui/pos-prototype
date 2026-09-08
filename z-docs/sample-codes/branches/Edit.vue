<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import branches from '@/routes/branches';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import FormError from '@/components/custom/FormError.vue';

const props = defineProps<{
    branch: {
        id: number;
        name: string;
        code: string;
        phone_number: string;
        email: string,
        address: string;
        city: string;
        is_active: boolean,
    }
}>();

interface BranchFormData {
    name: string;
    code: string;
    phone_number: string;
    email: string,
    address: string;
    city: string;
    is_active: boolean;
};

const form = useForm<BranchFormData>({
  name: props.branch.name,
  code: props.branch.code,
  phone_number: props.branch.phone_number,
  email: props.branch.email,
  address: props.branch.address,
  city: props.branch.city,
  is_active: props.branch.is_active ?? true,
});

const handleSubmit = () => {
    form.put(branches.update(props.branch.id).url);
};
</script>

<template>
    <Head title="Edit Branch: ${branch.name}" />

    <div class="AppContainer">
        <div class="w-6/12">
            <form @submit.prevent="handleSubmit">
                <div class="inputs_group">
                    <Label for="name" class="required">Name</Label>
                    <Input v-model="form.name" type="text" placeholder="Name of the branch" />
                    <FormError :error="form.errors.name" />
                </div>

                <div class="inputs_group">
                    <Label for="code">Branch Code</Label>
                    <Input v-model="form.code" type="text" placeholder="Branch Code (eg. BR-0002)" />
                    <FormError :error="form.errors.code" />
                </div>

                <div class="inputs_group">
                    <Label for="phone_number">Phone Number</Label>
                    <Input v-model="form.phone_number" type="text" placeholder="Phone Number" />
                    <FormError :error="form.errors.phone_number" />
                </div>

                <div class="inputs_group">
                    <Label for="email">Email Address</Label>
                    <Input v-model="form.email" type="text" placeholder="Email Address" />
                    <FormError :error="form.errors.email" />
                </div>

                <div class="inputs_group">
                    <Label for="address">Address</Label>
                    <Input v-model="form.address" type="text" placeholder="Address" />
                    <FormError :error="form.errors.address" />
                </div>

                <div class="inputs_group">
                    <Label for="city">City</Label>
                    <Input v-model="form.city" type="text" placeholder="City" />
                    <FormError :error="form.errors.city" />
                </div>

                <div class="inputs_group">
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="form.is_active" id="is_active" />
                        <Label for="is_active">Is Active?</Label>
                    </div>
                    <FormError :error="form.errors.is_active" />
                </div>

                <div class="flex justify-start space-x-3 pt-4">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ form.processing ? 'Updating Branch...' : 'Update Branch' }}
                    </Button>

                    <Link :href="branches.index().url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>