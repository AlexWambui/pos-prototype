# DB DESIGN

## Migrations

```php
// users
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('email')->unique();
    $table->unsignedTinyInteger('role')->default(4)->index();
    $table->unsignedTinyInteger('status')->default(1)->index();
    $table->string('image')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

Schema::create('branches', function (Blueprint $table) {
    $table->id();

    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('code')->unique();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('address')->nullable();
    $table->string('city')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('staff_profiles', function (Blueprint $table) {
    $table->id();
    $table->string('staff_code')->unique();
    $table->string('position')->index();
    $table->timestamp('hired_at')->nullable();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->timestamps();
});

Schema::create('customer_profiles', function (Blueprint $table) {
    $table->id();
    $table->string('customer_code')->nullable()->unique();
    $table->unsignedInteger('loyalty_points')->default(0);
    $table->decimal('credit_limit', 12, 2)->nullable();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
});

Schema::create('supplier_profiles', function (Blueprint $table) {
    $table->id();
    $table->string('company_name');
    $table->string('payment_terms')->index(); // net_30, net_60, prepaid
    $table->string('tax_id')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
});

Schema::create('shifts', function (Blueprint $table) {
    $table->id();
    $table->timestamp("opened_at");
    $table->timestamp("closed_at")->nullable();
    $table->decimal("opening_cash", 12, 2)->nullable();
    $table->decimal("closing_cash", 12, 2)->nullable();
    $table->foreignId("user_id")->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->index(['user_id', 'opened_at']);
});

Schema::create('product_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index('name');
    $table->index('is_active');
    $table->index(['is_active', 'sort_order']); // For active categories sorted by order
});

Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name')->unique();
    $table->string('sku')->unique()->nullable();
    $table->decimal('buying_price', 12, 2)->nullable(); // For profit calculation
    $table->decimal('selling_price', 12, 2);
    $table->string('barcode')->unique()->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('current_stock')->default(0);
    $table->decimal('weight_value', 10, 2)->nullable();
    $table->string('weight_unit')->nullable();
    $table->integer('sort_order')->default(0);
    $table->string('image')->nullable();
    $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
    $table->timestamps();

    $table->index('name');
    $table->index('is_active');
    $table->index('current_stock');
    $table->index('sort_order');
    $table->index('product_category_id');
    $table->index(['is_active', 'sort_order']); // For active products sorted by order
    $table->index(['product_category_id', 'is_active']); // For filtering by category and active status
    $table->index(['is_active', 'current_stock']); // For low stock queries on active products
});

Schema::create('inventory_movements', function (Blueprint $table) {
    $table->id();
    $table->unsignedTinyInteger('type'); // sale, restock, adjustment, return, waste
    $table->integer('quantity_change'); // Positive for in, negative for out
    $table->text('reason')->nullable(); // "stock take", "damaged"
    $table->string('reference_type')->nullable(); // "App\Models\|Sale"
    $table->foreignId('reference_id')->nullable(); // Links to purchese_order_id, sale_id
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('created_at');
    $table->index(['product_id', 'reference_type', 'reference_id', 'created_at']);
});

Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('sale_number')->unique();
    $table->unsignedTinyInteger('status')->default(1);
    $table->decimal('total_amount', 12, 2);
    $table->foreignId('shift_id')->constrained()->restrictOnDelete();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->timestamp('completed_at');
    $table->timestamps();

    $table->index(['shift_id', 'completed_at']);
});

Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->string('product_name');
    $table->string('sku');
    $table->decimal('unit_price', 12, 2);
    $table->integer('quantity');
    $table->decimal('line_total', 12, 2);
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->timestamps();
});

Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->string('method'); // cash, card, mpesa
    $table->decimal('amount', 12, 2);
    $table->string('reference')->nullable();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
});

Schema::create('cash_movements', function (Blueprint $table) {
    $table->id();
    $table->string('type'); // opening, sale, payout, topup, closing
    $table->decimal('amount', 12, 2);
    $table->string('note')->nullable();
    $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->timestamps();
});
```

## MODELS

```php
class User extends Authenticatable
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function staffProfile()
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function supplierProfile()
    {
        return $this->hasOne(SupplierProfile::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
```


Original page header
```js
<!-- Header with Stats and Actions -->
<div class="header mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold">Users</h1>

        <div class="search-filter-bar">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <!-- Search Input -->
                <div class="flex-1">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Search users by name or email..."
                        class="w-full"
                    />
                </div>

                <!-- Active Filters Display -->
                <div class="flex flex-wrap gap-2 items-center">
                    <span v-if="search || selectedRole" class="text-sm text-gray-600">
                        Filters:
                    </span>
                    <span v-if="search" 
                        class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full flex items-center gap-1">
                        Search: "{{ search }}"
                        <button @click="search = ''" class="text-blue-600 hover:text-blue-800">×</button>
                    </span>
                    <span v-if="selectedRole" 
                        class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full flex items-center gap-1">
                        Role: {{ role_counts[selectedRole]?.label || selectedRole }}
                        <button @click="selectedRole = ''" class="text-green-600 hover:text-green-800">×</button>
                    </span>
                    <!-- <Button v-if="search || selectedRole" 
                            @click="clearFilters" 
                            variant="outline" 
                            size="sm">
                        Clear All
                    </Button> -->
                </div>
            </div>
        </div>

        <Link :href="users.create().url">
            <Button>Create User</Button>
        </Link>
    </div>

    <!-- Role Statistics -->
    <div class="role-stats mb-4 py-2 px-4 bg-gray-50 rounded-lg">
        <div class="flex flex-wrap gap-8 text-sm">
            <div class="stat-item cursor-pointer" 
                    @click="filterByRole('')"
                    :class="{ 'font-bold text-blue-600': !selectedRole }">
                <span class="font-semibold">{{ totalUsers }}</span> Users
            </div>
            <div v-for="(roleCount, roleValue) in role_counts" 
                    :key="roleValue"
                    class="stat-item cursor-pointer"
                    @click="filterByRole(roleValue)"
                    :class="{ 'font-bold text-blue-600': selectedRole === roleValue }">
                <span class="font-semibold">{{ roleCount.count }}</span> {{ roleCount.label }}{{ roleCount.count !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>
</div>
```

## ENUMS

```php
USER_ROLES: [
    SUPER_ADMIN = 0;
    ADMIN = 1;
    OWNER = 2;
    USER = 3;
]

USER_STATUSES: [
    INACTIVE = 0;
    ACTIVE = 1;
    BANNED = 2;
]

INVENTORY_MOVEMENT_TYPE: [
    SALE = 'sale';
    RESTOCK = 'restock';
    ADJUSTMENT = 'adjustment';
]

SALE_STATUS: [
    PENDING = 0;
    COMPLETED = 1;
]

protected $casts = [
    'role' => UserRole::class,
    'status' => UserStatus::class,
];
```
