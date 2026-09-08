# Creating a User Factory Pattern for Creating Different User Types

A factory pattern helps create different user types with their specific profiles in a clean, organized way. This is especially useful for:

- Admin interfaces (like your user creation form)
- API endpoints for creating specific user types
- Seeding database with realistic data
- Testing different user scenarios

Benefits:

- Clean separation: User creation logic is separated from controllers.
- Reusability: Same factory can be used in controllers, seeders, and tests.
- Flexibility: Easy to add new user types.
- Maintainability: Changes to user creation logic only need to be made in one place.

## Creating the User Factory

```php
<?php

namespace App\Factories;

use App\Models\User;
use App\Models\Users\StaffProfile;
use App\Models\Users\CustomerProfile;
use App\Models\Users\SupplierProfile;
use App\Enums\UserRoles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory
{
    protected array $userData = [];
    protected array $profileData = [];
    protected ?UserRoles $role = null;
    
    /**
     * Create a new user factory instance
     */
    public static function make(): self
    {
        return new self();
    }
    
    /**
     * Set user basic information
     */
    public function withUserData(array $data): self
    {
        $this->userData = array_merge($this->userData, $data);
        return $this;
    }
    
    /**
     * Set the user role
     */
    public function withRole(UserRoles $role): self
    {
        $this->role = $role;
        return $this;
    }
    
    /**
     * Set profile-specific data
     */
    public function withProfileData(array $data): self
    {
        $this->profileData = $data;
        return $this;
    }
    
    /**
     * Create a cashier user
     */
    public function createCashier(): User
    {
        $this->role = UserRoles::CASHIER;
        
        $user = $this->createBaseUser();

        // Optional: If created_by isn't set manually, use the current authenticated user
        if (!isset($this->userData['created_by']) && auth()->check()) {
            $user->created_by = auth()->id();
            $user->save();
        }
        
        $staffProfile = StaffProfile::create(array_merge([
            'staff_code' => $this->profileData['staff_code'] ?? StaffProfile::generateUniqueCode(),
            'position' => $this->profileData['position'] ?? 'Cashier',
            'hired_at' => $this->profileData['hired_at'] ?? now(),
            'branch_id' => $this->profileData['branch_id'] ?? null,
        ], $this->profileData));
        
        $user->staffProfile()->save($staffProfile);
        
        return $user->load('staffProfile');
    }
    
    /**
     * Create a customer user
     */
    public function createCustomer(): User
    {
        $this->role = UserRoles::CUSTOMER;
        
        $user = $this->createBaseUser();
        
        $customerProfile = CustomerProfile::create(array_merge([
            'customer_code' => $this->profileData['customer_code'] ?? CustomerProfile::generateUniqueCode(),
            'loyalty_points' => $this->profileData['loyalty_points'] ?? 0,
            'credit_limit' => $this->profileData['credit_limit'] ?? null,
            'branch_id' => $this->profileData['branch_id'] ?? null,
        ], $this->profileData));
        
        $user->customerProfile()->save($customerProfile);
        
        return $user->load('customerProfile');
    }
    
    /**
     * Create a supplier user
     */
    public function createSupplier(): User
    {
        $this->role = UserRoles::SUPPLIER;
        
        $user = $this->createBaseUser();
        
        $supplierProfile = SupplierProfile::create(array_merge([
            'company_name' => $this->profileData['company_name'],
            'payment_terms' => $this->profileData['payment_terms'] ?? 'net_30',
            'tax_id' => $this->profileData['tax_id'] ?? null,
            'is_active' => $this->profileData['is_active'] ?? true,
            'branch_id' => $this->profileData['branch_id'] ?? null,
        ], $this->profileData));
        
        $user->supplierProfile()->save($supplierProfile);
        
        return $user->load('supplierProfile');
    }
    
    /**
     * Create an admin user
     */
    public function createAdmin(): User
    {
        $this->role = UserRoles::ADMIN;
        
        return $this->createBaseUser();
    }
    
    /**
     * Create super admin user
     */
    public function createSuperAdmin(): User
    {
        $this->role = UserRoles::SUPER_ADMIN;
        
        return $this->createBaseUser();
    }
    
    /**
     * Generic create method based on role
     */
    public function create(): User
    {
        if (!$this->role) {
            throw new \Exception('Role must be set before creating user');
        }
        
        return match($this->role) {
            UserRoles::CASHIER => $this->createCashier(),
            UserRoles::CUSTOMER => $this->createCustomer(),
            UserRoles::SUPPLIER => $this->createSupplier(),
            UserRoles::ADMIN => $this->createAdmin(),
            UserRoles::SUPER_ADMIN => $this->createSuperAdmin(),
        };
    }
    
    /**
     * Create base user without profile
     */
    protected function createBaseUser(): User
    {
        return User::create(array_merge([
            'uuid' => Str::uuid(),
            'name' => $this->userData['name'] ?? 'System User',
            'email' => $this->userData['email'] ?? $this->generateEmail(),
            'password' => Hash::make($this->userData['password'] ?? 'password123'),
            'role' => $this->role->value,
            'status' => $this->userData['status'] ?? 1,
        ], $this->userData));
    }
    
    /**
     * Generate a unique email
     */
    protected function generateEmail(): string
    {
        return strtolower(str_replace(' ', '.', $this->userData['name'] ?? 'user')) . 
               '@' . ($this->role?->name ?? 'system') . 
               '.example.com';
    }
}

// Usage Examples:
// In your UserController store method:
public function store(UserRequest $request)
{
    try {
        DB::beginTransaction();
        
        $user = UserFactory::make()
            ->withUserData($request->only(['name', 'email', 'password']))
            ->withRole(UserRoles::from($request->role))
            ->withProfileData($request->except(['name', 'email', 'password', 'role']))
            ->create();
        
        DB::commit();
        
        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()]);
    }
}

// In your seeders:
public function run(): void
{
    // Create a manager
    UserFactory::make()
        ->withUserData([
            'name' => 'John Manager',
            'email' => 'manager@example.com',
        ])
        ->withRole(UserRoles::CASHIER)
        ->withProfileData([
            'position' => 'Manager',
            'branch_id' => 1,
        ])
        ->createCashier();
    
    // Create 10 random customers
    for ($i = 0; $i < 10; $i++) {
        UserFactory::make()
            ->withUserData([
                'name' => fake()->name(),
                'email' => fake()->email(),
            ])
            ->withRole(UserRoles::CUSTOMER)
            ->withProfileData([
                'loyalty_points' => fake()->numberBetween(0, 1000),
                'branch_id' => fake()->numberBetween(1, 5),
            ])
            ->createCustomer();
    }
}
```

## Policies

Why Policies?

- Authorization: Control what users can do based on their roles
- Security: Prevent unauthorized access to resources
- Centralized: All authorization logic in one place
- Readable: Clear, declarative permission checks

### Registering policies

```php
// In App\Providers\AuthServiceProvider.php

use App\Models\User;
use App\Models\Branch;
use App\Policies\UserPolicy;
use App\Policies\BranchPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Branch::class => BranchPolicy::class,
    ];
    
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

### Using Policies in Controllers
```php
// In your UserController
public function index(Request $request)
{
    $this->authorize('viewAny', User::class);
    
    // ... rest of the method
}

public function update(UserRequest $request, User $user)
{
    $this->authorize('update', $user);
    
    // ... rest of the method
}

// In your Vue components with Inertia
// You can pass permissions to the frontend
public function create()
{
    $this->authorize('create', User::class);
    
    return Inertia::render('users/Create', [
        'branches' => Branch::all(),
        'can' => [
            'create_users' => auth()->user()->can('create', User::class),
            'edit_users' => auth()->user()->can('update', User::class),
        ]
    ]);
}
```

### Using Policies in Blade Files

```php
@can('create', App\Models\User::class)
    <a href="{{ route('users.create') }}">Create User</a>
@endcan
```

## Events

Why Events?
- Separation of concerns: Keep user creation logic clean
- Extensibility: Easy to add new behaviors without modifying existing code
- Asynchronous processing: Send emails, notifications, etc. without slowing down the request

### Events Implementation

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCreated
{
    use Dispatchable, SerializesModels;
    
    public User $user;
    public string $password; // Optional: for sending temporary passwords
    
    /**
     * Create a new event instance.
     */
    public function __construct(User $user, ?string $password = null)
    {
        $this->user = $user;
        $this->password = $password;
    }
}

// More specific events
class CashierCreated extends UserCreated
{
    // You can add cashier-specific properties
}

class CustomerCreated extends UserCreated
{
    public function __construct(User $user, public int $initialPoints = 0)
    {
        parent::__construct($user);
    }
}
```
### Event Listeners

```php
<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmail
{
    public function handle(UserCreated $event): void
    {
        // Send welcome email based on user role
        $mailable = new WelcomeEmail($event->user);
        
        if ($event->password) {
            $mailable->withTemporaryPassword($event->password);
        }
        
        Mail::to($event->user->email)->send($mailable);
    }
}

class CreateDefaultSettings
{
    public function handle(UserCreated $event): void
    {
        // Create default settings for the user
        $event->user->settings()->create([
            'theme' => 'light',
            'notifications_enabled' => true,
            'language' => 'en',
        ]);
    }
}

class LogUserCreation
{
    public function handle(UserCreated $event): void
    {
        Log::info('User created', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'role' => $event->user->role->label(),
            'created_by' => auth()->id(),
        ]);
    }
}

class AssignDefaultPermissions
{
    public function handle(UserCreated $event): void
    {
        // Assign default permissions based on role
        if ($event->user->isCashier()) {
            $event->user->assignRole('cashier');
            // Grant cashier-specific permissions
            $event->user->givePermissionTo([
                'view_products',
                'process_sales',
                'view_reports_own',
            ]);
        }
        
        if ($event->user->isCustomer()) {
            $event->user->assignRole('customer');
            // Grant customer permissions
            $event->user->givePermissionTo([
                'view_products',
                'view_own_orders',
            ]);
        }
    }
}

class CreateUserWallet
{
    public function handle(UserCreated $event): void
    {
        // Create wallet for the user if they are a customer
        if ($event->user->isCustomer()) {
            $event->user->wallet()->create([
                'balance' => 0,
                'currency' => 'USD',
            ]);
        }
    }
}
```
### Registering Events and Listeners

```php
// In App\Providers\EventServiceProvider.php

protected $listen = [
    UserCreated::class => [
        SendWelcomeEmail::class,
        CreateDefaultSettings::class,
        LogUserCreation::class,
        AssignDefaultPermissions::class,
        CreateUserWallet::class,
    ],
    
    CashierCreated::class => [
        SendWelcomeEmail::class,
        CreateCashierSchedule::class,
    ],
    
    CustomerCreated::class => [
        SendWelcomeEmail::class,
        CreateUserWallet::class,
        SendDiscountCoupon::class,
    ],
];
```

### Firing Events in your code
```php
// In your UserFactory or Controller
public function createCashier(): User
{
    $user = $this->createBaseUser();
    
    $staffProfile = StaffProfile::create([...]);
    $user->staffProfile()->save($staffProfile);
    
    // Fire the event
    event(new UserCreated($user, $this->userData['password'] ?? null));
    
    return $user;
}
```

### Queueing Events (Async Processing)
```php
// Make your event implement ShouldQueue
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserCreated $event): void
    {
        // This will run in the queue, not blocking the response
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }
}
```

## The End
