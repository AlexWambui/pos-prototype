<script setup lang="ts">
import { Link, Head, useForm } from '@inertiajs/vue3';
import { ImagePlus, X } from '@lucide/vue';
import { ref } from 'vue';
import FormHeader from '@/components/custom/FormHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import productRoutes from '@/routes/products';

interface Category {
    id: number;
    name: string;
}

interface ProductImage {
    id: number;
    name: string;
    image_url: string;
}

interface Product {
    id: number;
    name: string;
    description: string | null;
    cost_price: string | null;
    price: string;
    sku: string | null;
    is_featured: boolean;
    is_active: boolean;
    is_new: boolean;
    product_category_id: number | null;
    images: ProductImage[];
}

const props = defineProps<{
    product_categories: Category[];
    product: Product;
}>();

type ImageItem = {
    id?: number;
    file?: File;
    preview: string;
};

const form = useForm({
    name: props.product.name,
    description: props.product.description || '',
    cost_price: props.product.cost_price || '',
    price: props.product.price,
    sku: props.product.sku || '',
    is_featured: props.product.is_featured,
    is_active: props.product.is_active,
    is_new: props.product.is_new,
    product_category_id: props.product.product_category_id,
    images: [] as File[],
    images_to_delete: [] as number[],
    _method: 'PUT',
});

const MAX_IMAGES = 5;
const MAX_FILE_SIZE = 2 * 1024 * 1024;

const images = ref<ImageItem[]>(
    props.product.images.map(img => ({
        id: img.id,
        preview: img.image_url,
    }))
);

const isMaxImagesReached = () => images.value.length >= MAX_IMAGES;

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const files = Array.from(target.files || []);

    if (!files.length) {
        return;
    }

    if (images.value.length + files.length > MAX_IMAGES) {
        alert(`Max ${MAX_IMAGES} images allowed`);
        target.value = '';

        return;
    }

    for (const file of files) {
        if (file.size > MAX_FILE_SIZE) {
            alert(`${file.name} exceeds 2MB`);
            target.value = '';

            return;
        }
    }

    files.forEach(file => {
        if (!file.type.startsWith('image/')) {
            return;
        }

        images.value.push({
            file,
            preview: URL.createObjectURL(file),
        });
    });

    target.value = '';
};

const removeImage = (index: number) => {
    const item = images.value[index];

    if (item.id) {
        form.images_to_delete.push(item.id);
    }

    if (item.preview.startsWith('blob:')) {
        URL.revokeObjectURL(item.preview);
    }

    images.value.splice(index, 1);
};

const submitForm = () => {
    form.images = images.value
        .filter(i => i.file)
        .map(i => i.file as File);

    form.post(
        productRoutes.update({
            product: props.product.id
        }).url,
        {
            forceFormData: true,
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head title="Edit Product" />

    <div class="form">
        <FormHeader :backUrl="productRoutes.index().url" title="Edit Product" />

        <form @submit.prevent="submitForm" enctype="multipart/form-data">
            <input type="hidden" name="_method" value="PUT" />

            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="name" class="required">Product Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Product name"
                            autofocus
                        />
                        <InputError :error="form.errors.name" />
                    </div>

                    <div class="inputs-group">
                        <Label for="product_category_id">Product Category</Label>
                        <Select v-model="form.product_category_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select product category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem :value="null">None</SelectItem>
                                    <SelectItem 
                                        v-for="option in product_categories" 
                                        :key="option.id"
                                        :value="option.id"
                                    >
                                        {{ option.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.product_category_id" />
                    </div>
                </div>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="sku">SKU</Label>
                        <Input
                            id="sku"
                            v-model="form.sku"
                            type="text"
                            placeholder="SKU"
                        />
                        <InputError :message="form.errors.sku" />
                    </div>
                </div>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="cost_price">Cost Price</Label>
                        <Input
                            id="cost_price"
                            v-model="form.cost_price"
                            type="number"
                            step="0.01"
                            placeholder="Cost Price"
                        />
                        <InputError :message="form.errors.cost_price" />
                    </div>

                    <div class="inputs-group">
                        <Label for="price" class="required">Selling Price</Label>
                        <Input
                            id="price"
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            required
                            placeholder="Selling Price"
                        />
                        <InputError :message="form.errors.price" />
                    </div>
                </div>

                <div class="inputs-group">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Describe your product..."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="inputs-group-wrapper-3">
                    <div class="inputs-group">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0" />
                            <input 
                                type="checkbox" 
                                id="is_active" 
                                v-model="form.is_active"
                                value="1"
                                class="w-4 h-4 rounded"
                            />
                            <Label for="is_active">Active</Label>
                        </div>
                    </div>

                    <div class="inputs-group">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_featured" value="0" />
                            <input 
                                type="checkbox" 
                                id="is_featured" 
                                v-model="form.is_featured"
                                value="1"
                                class="w-4 h-4 rounded"
                            />
                            <Label for="is_featured">Featured Product</Label>
                        </div>
                    </div>

                    <div class="inputs-group">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_new" value="0" />
                            <input 
                                type="checkbox" 
                                id="is_new" 
                                v-model="form.is_new"
                                value="1"
                                class="w-4 h-4 rounded"
                            />
                            <Label for="is_new">New</Label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Product Images</h3>

                <div class="grid grid-cols-3 md:grid-cols-5 gap-3">

                    <div
                        v-for="(item, index) in images"
                        :key="item.id ?? item.preview"
                        class="relative aspect-square border rounded-xl overflow-hidden"
                    >
                        <img :src="item.preview" class="w-full h-full object-cover" />

                        <button
                            type="button"
                            @click="removeImage(index)"
                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1"
                        >
                            <X class="w-3 h-3" />
                        </button>

                        <span v-if="index === 0" class="absolute bottom-1 left-1 text-xs bg-black text-white px-1 rounded">
                            Primary
                        </span>
                    </div>

                    <label
                        v-if="!isMaxImagesReached()"
                        class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl aspect-square cursor-pointer"
                    >
                        <ImagePlus class="w-6 h-6 text-gray-400" />
                        <input type="file" multiple accept="image/*" class="hidden" @change="handleImageChange" />
                    </label>

                </div>

                <InputError :message="form.errors.images" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Update Product
                </Button>

                <div>
                    <Link :href="productRoutes.index().url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>