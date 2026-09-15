<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Loader2, AlertCircle } from '@lucide/vue';
import { ref, computed } from 'vue';
import AppPageHeader from '@/components/custom/AppPageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Pagination from '@/components/custom/Pagination.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import branchRoutes from '@/routes/branches';

interface Branch {
    id: number;
    uuid: string;
    name: string;
    code: string;
    phone_number: string;
    email: string;
    address: string;
    city: string;
    is_active: boolean;
}

interface Props {
    branches: {
        data: Branch[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        };
    };
    total: number;
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');

const handleSearch = (value: string) => {
    router.get(branchRoutes.index().url, {
        search: value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const hasActiveFilters = computed(() => !!search.value);

const getDisplayRange = computed(() => {
    const { current_page, per_page, total } = props.branches.meta;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);

    return { start, end, total };
});
</script>

<template>
    <AppPageHeader
        resourceName="Branches"
        v-model="search"
        search-placeholder="Search by name..."
        create-url="/branches/create"
        create-label="Branch"
        @search="handleSearch"
    />

    <div class="table-wrapper">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="id">#</TableHead>
                    <TableHead>Branch</TableHead>
                    <TableHead>Code</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>City</TableHead>
                    <TableHead>Address</TableHead>
                    <TableHead class="actions">Actions</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow v-for="(branch, index) in branches.data" :key="branch.id">
                    <TableCell class="id">{{ (branches.meta.current_page - 1) * branches.meta.per_page + index + 1 }}</TableCell>
                    <TableCell :class="{ 'text-red-500 font-medium' : branch.is_active === false, 'text-green-600 font-medium' : branch.is_active === true }">{{ branch.name }}</TableCell>
                    <TableCell>{{ branch.code ?? '-' }}</TableCell>
                    <TableCell>{{ branch.phone_number ?? '-' }}</TableCell>
                    <TableCell>{{ branch.email ?? '-' }}</TableCell>
                    <TableCell>{{ branch.city ?? '-' }}</TableCell>
                    <TableCell>{{ branch.address ?? '-' }}</TableCell>
                    <TableCell class="actions w-20">
                        <div class="actions-wrapper">
                            <Link :href="branchRoutes.edit(branch.uuid).url" class="action edit">
                                <Pencil />
                            </Link>
                            <span class="divider">|</span>
                            <DeleteConfirmationDialog 
                                :url="branchRoutes.destroy(branch.uuid).url" 
                                title="Delete Branch?" 
                                description="This branch and it's associated data will be deleted permanently!" 
                                confirm-text="Delete Branch"
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

                <TableRow v-if="branches.data.length === 0">
                    <TableCell colspan="9" class="blank-table-row">
                        No branches found.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <Pagination :meta="branches.meta" />

    <div class="table-results-summary">
        <p>
            Showing {{ getDisplayRange.start }} to {{ getDisplayRange.end }}
            of {{ getDisplayRange.total }} branches
        </p>
        <p v-if="hasActiveFilters" class="filtered-results">
            Filtered results
        </p>
    </div>
</template>
