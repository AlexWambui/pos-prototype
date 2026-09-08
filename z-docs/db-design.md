# DB DESIGN

## Migrations

```php
// users
users {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('phone_number')->nullable();
    $table->unsignedTinyInteger('role')->default(4)->index();
    $table->unsignedTinyInteger('status')->default(1)->index();
    $table->string('image')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
}

branches {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('code')->unique();
    $table->string('phone_number')->nullable();
    $table->string('email')->nullable();
    $table->string('city')->nullable();
    $table->string('address')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
}

staff_profiles {
    $table->id();
    $table->string('staff_code')->unique();
    $table->string('position')->index();
    $table->timestamp('hired_at')->nullable();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
}

customer_profiles {
    $table->id();
    $table->string('customer_code')->nullable()->unique();
    $table->unsignedInteger('loyalty_points')->default(0);
    $table->decimal('credit_limit', 12, 2)->nullable();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
}

supplier_profiles {
    $table->id();
    $table->string('company_name');
    $table->string('payment_terms')->index(); // net_30, net_60, prepaid
    $table->string('tax_id')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
}

shifts {
    $table->id();
    $table->timestamp("shift_start")->nullable();
    $table->timestamp("shift_end")->nullable();
    $table->decimal("opening_cash", 12, 2)->nullable();
    $table->decimal("closing_cash", 12, 2)->nullable();
    $table->decimal('total_sales_amount', 10, 2)->default(0.00);
    $table->decimal('total_commission', 10, 2)->default(0.00);
    $table->text('notes')->nullable();
    $table->foreignId("user_id")->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->index(['user_id', 'shift_start']);
}

product_categories {
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
}

products {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->string('sku')->unique()->nullable();
    $table->decimal('buying_price', 12, 2)->nullable(); // For profit calculation
    $table->decimal('selling_price', 12, 2);
    $table->decimal('discount_price', 10, 2)->default(0.00)->nullable();
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
    $table->index('slug');
    $table->index('is_active');
    $table->index('current_stock');
    $table->index('sort_order');
    $table->index('product_category_id');
    $table->index(['is_active', 'sort_order']); // For active products sorted by order
    $table->index(['product_category_id', 'is_active']); // For filtering by category and active status
    $table->index(['is_active', 'current_stock']); // For low stock queries on active products
}

product_images {
    $table->id();
    $table->string('image');
    $table->smallInteger('image_order')->default(5);

    $table->foreignId('product_id')->constrained('products');
    $table->timestamps();
}

inventory_movements {
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
}

orders {
    $table->id();
    $table->string('reference_number')->unique();
    $table->string('sale_type')->default('POS');
    $table->string('status')->default('pending');
    $table->string('discount_code')->nullable();
    $table->decimal('discount',10,2)->default(0.00);
    $table->decimal('total_amount', 10,2)->default(0.00);
    $table->decimal('amount_paid', 10,2)->default(0.00);
    $table->foreignId('shift_id')->constrained()->restrictOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('completed_at');
    $table->timestamps();

    $table->index(['shift_id', 'completed_at']);
}

order_items {
    $table->id();
    $table->string('product_name');
    $table->string('product_sku');
    $table->integer('quantity');
    $table->decimal('selling_price', 10, 2)->default(0.00);
    $table->decimal('buying_price', 10, 2)->default(0.00);
    $table->decimal('line_total', 10, 2)->default(0.00);
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->nullOnDelete();
    $table->timestamps();
}

payments {
    $table->id();
    $table->string('method'); // cash, card, mpesa
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending');
    $table->string('transaction_reference')->nullable();
    $table->json('payment_metadata')->nullable();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->timestamp('paid_at');
    $table->timestamps();
}

cash_movements {
    $table->id();
    $table->string('type'); // opening, sale, payout, topup, closing
    $table->decimal('amount', 12, 2);
    $table->string('note')->nullable();
    $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->timestamps();
}

settings {
    $table->id();
    $table->string('company_name');
    $table->string('location');
    $table->string('phone_number');
    $table->string('other_phone_number')->nullable();
    $table->string('email');
    $table->string('currency')->default('KES');
    $table->json('commission_tiers')->nullable();
    $table->string('logo')->nullable();
    $table->timestamps();
}
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
