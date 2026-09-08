<script setup lang="ts">
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';
import branches from '@/routes/branches';
import PageHeader from '@/components/custom/PageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Toast from '@/components/custom/ToastNotification/Index.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Branches',
                href: branches.index().url,
            },
        ],
    },
});

const page = usePage<any>();

interface Branch {
    id: number;
    name: string;
    code: string;
    phone_number: string;
    email: string;
    address: string;
    city: string;
    is_active: boolean;
}

interface Props {
    branches: Branch[];
    total: number;
    filters: {
        search?: string;
    };
};

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

const performSearch = debounce(() => {
    router.get(branches.index().url, {
        search: search.value,
    }, {
        preserveState: true,
        replace: true
    });
}, 300);

watch(search, () => {
    performSearch();
});
</script>

<template>
    <Head title="Branches" />

    <div class="AppContainer">
        <Toast v-if="page.props.flash?.message" 
            :message="page.props.flash.message" 
            :type="page.props.flash.type" 
            :duration="5000" 
        />

        <PageHeader 
            title="Branches"
            v-model:search="search"
            :create-href="branches.create().url"
            create-button-text="Create Branch"
            search-placeholder="Search branches by name..."
        />

        <div class="branches_table">
            <div class="bg-white dark:bg-gray-900 rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50 dark:bg-gray-900">
                            <TableHead class="w-[50px]">#</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Address</TableHead>
                            <TableHead>City</TableHead>
                            <TableHead class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="(branch, index) in props.branches" 
                                 :key="branch.id"
                                 class="hover:bg-gray-50">
                            <TableCell class="font-medium">{{ index + 1 }}</TableCell>
                            <TableCell :class="{ 'text-red-500' : branch.is_active === false, 'text-green-500' : branch.is_active === true }">{{ branch.name }}</TableCell>
                            <TableCell>{{ branch.code ?? '-' }}</TableCell>
                            <TableCell>{{ branch.phone_number ?? '-' }}</TableCell>
                            <TableCell>{{ branch.email ?? '-' }}</TableCell>
                            <TableCell>{{ branch.address ?? '-' }}</TableCell>
                            <TableCell>{{ branch.city ?? '-' }}</TableCell>
                            <TableCell class="text-center">
                                <div class="flex justify-center space-x-2">
                                    <Link :href="branches.edit(branch.id).url" 
                                          class="text-blue-600 hover:text-blue-800 hover:underline">
                                        Edit
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <DeleteConfirmationDialog 
                                        :url="branches.destroy(branch.id).url" 
                                        title="Delete Branch?" 
                                        description="This branch will be deleted permanently!" 
                                        confirm-text="Delete Branch">
                                        <template #trigger>
                                            <button class="text-red-600 hover:text-red-800 hover:underline">
                                                Delete
                                            </button>
                                        </template>
                                    </DeleteConfirmationDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="props.branches.length === 0">
                            <TableCell colspan="8" class="text-center py-8 text-gray-500">
                                No branches found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 text-gray-600 text-sm flex justify-center items-center gap-4">
                <div>Showing {{ props.total }} branches</div>
                <div v-if="search" class="text-blue-600">
                    Filtered results
                </div>
            </div>
        </div>
    </div>
</template>