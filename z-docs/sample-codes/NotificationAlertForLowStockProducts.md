# Notificaction for low stock products

Recommended Approach: Event + Listener

1.Define the Event

```php
<?php

namespace Modules\Product\Events;

use Modules\Product\Models\InventoryMovement;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

class StockLevelChanged
{
    use Dispatchable;

    public function __construct(
        public Product $product,
        public InventoryMovement $movement,
        public string $previousStatus,  // 'in_stock' | 'low_stock' | 'out_of_stock'
        public string $newStatus,
    ) {}
}
```

Why pass both previousStatus and newStatus? Because you only want to notify on transitions, not on every change. If stock goes from 4 → 3 with a threshold of 5, both states are "low stock" — you don't want to nag the user again. If it goes 6 → 4, that's a real transition into low stock and worth a notification.

2.Compute Status Consistently

Put the status logic in one place — the Product model:

// Product.php

```php
public const STOCK_IN_STOCK = 'in_stock';
public const STOCK_LOW = 'low_stock';
public const STOCK_OUT = 'out_of_stock';
public const STOCK_UNLIMITED = 'unlimited';

public function stockStatus(): string
{
    if (!$this->tracksInventory()) {
        return self::STOCK_UNLIMITED;
    }

    $stock = (float) $this->current_stock;
    $threshold = (float) $this->low_stock_threshold;

    if ($stock <= 0) {
        return self::STOCK_OUT;
    }

    if ($stock <= $threshold) {
        return self::STOCK_LOW;
    }

    return self::STOCK_IN_STOCK;
}
```

Now every consumer — resources, events, notifications — uses the same definition. No drift.

3.Dispatch the Event From updateStock / addStock / removeStock

In each method, before you write, capture $previousStatus. After you write, compute $newStatus. Fire only if they differ:

```php
public function removeStock(...): InventoryMovement {
    $this->ensureInventoryIsTracked();

    return DB::transaction(function () use (...) {
        $locked = static::query()->lockForUpdate()->find($this->id);

        $previousStatus = $locked->stockStatus();  // 👈 before

        $before = (float) $locked->current_stock;
        $after  = $before - $quantity;

        if ($after < 0) {
            throw new InsufficientStockException(...);
        }

        $locked->update(['current_stock' => $after]);
        $this->current_stock = $after;

        $movement = $locked->inventoryMovements()->create([...]);

        $newStatus = $locked->stockStatus();       // 👈 after

        if ($previousStatus !== $newStatus) {
            StockLevelChanged::dispatch($locked, $movement, $previousStatus, $newStatus);
        }

        return $movement;
    });
}
```

Do the same in addStock and updateStock. Yes, it's a bit repetitive — but keeping the event dispatch inside the transaction ensures the movement and the event are consistent.

Alternative: Use a model observer on InventoryMovement::created and reconstruct the previous status from quantity_before. This centralizes the logic but requires computing status from a raw quantity, which duplicates the stockStatus() logic. I'd keep it explicit in the three methods.

4.Create the Listener

```php
<?php

namespace Modules\Product\Listeners;

use Modules\Product\Events\StockLevelChanged;
use Modules\Product\Notifications\LowStockNotification;
use Modules\Product\Notifications\OutOfStockNotification;
use Illuminate\Support\Facades\Notification;
use Modules\User\Enums\UserRoles;
use Modules\User\Models\User;

class SendStockLevelNotification
{
    public function handle(StockLevelChanged $event): void
    {
        $product = $event->product;

        // Only notify when *entering* a problematic state
        if ($event->newStatus === $event->previousStatus) {
            return;
        }

        // Don't notify when stock is recovering (out → low, low → in)
        if ($event->previousStatus === 'out_of_stock') {
            return; // optional: skip recovery notifications
        }

        $recipients = $this->getRecipients();

        match ($event->newStatus) {
            'low_stock' => Notification::send(
                $recipients,
                new LowStockNotification($product, $event->movement)
            ),
            'out_of_stock' => Notification::send(
                $recipients,
                new OutOfStockNotification($product, $event->movement)
            ),
            default => null,
        };
    }

    protected function getRecipients()
    {
        return User::query()
            ->whereIn('role', [
                UserRoles::ADMIN->value,
                UserRoles::SUPER_ADMIN->value,
            ])
            ->where('status', UserStatuses::ACTIVE->value)
            ->get();
    }
}
```

Key idea: only notify when entering a bad state, not when recovering. Users don't want "your product is back in stock" spam. (Unless they do — then add a StockRecovered event.)

5.Register the Listener

In EventServiceProvider (or wherever your module registers events):

```php
protected $listen = [
    \Modules\Product\Events\StockLevelChanged::class => [
        \Modules\Product\Listeners\SendStockLevelNotification::class,
    ],
];
```

6.Create the Notifications

```php
<?php

namespace Modules\Product\Notifications;

use Modules\Product\Models\InventoryMovement;
use Modules\Product\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public InventoryMovement $movement,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];  // add 'mail' when ready
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => (float) $this->product->current_stock,
            'threshold' => (float) $this->product->low_stock_threshold,
            'url' => route('products-inventory.index'),
            'message' => "{$this->product->name} is low on stock "
                . "({$this->product->current_stock} left, threshold {$this->product->low_stock_threshold}).",
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock: {$this->product->name}")
            ->line("{$this->product->name} is running low.")
            ->line("Current stock: {$this->product->current_stock}")
            ->line("Threshold: {$this->product->low_stock_threshold}")
            ->action('View Inventory', route('products-inventory.index'));
    }
}
```

Same shape for OutOfStockNotification — different subject, different message.

7.Add notifications Table (if you don't have it)

Laravel ships this migration:

```bash

php artisan notifications:table
php artisan migrate
```

Then via($notifiable) returning ['database'] writes to notifications, and you can render them in a bell icon dropdown in your Inertia layout.
Showing the Current State in the UI (Approach A)

Events only fire on transitions. If a product is already low on stock and nobody changes it, no notification fires. That's fine for notifications, but you still want at-a-glance awareness in the inventory page.

You already do this with stats.low_stock_count and stats.out_of_stock_count — those cards are the "read-time" view. Extend them:

// ProductInventoryController::index

```php
$stats = [
    'total_products' => Product::count(),
    'low_stock_count' => Product::query()
        ->where('track_inventory', true)
        ->where('current_stock', '>', 0)
        ->whereColumn('current_stock', '<=', 'low_stock_threshold')
        ->count(),
    'out_of_stock_count' => Product::query()
        ->where('track_inventory', true)
        ->where('current_stock', '<=', 0)
        ->count(),
    'total_value' => ...,
];
```

Consider highlighting the low/out cards when counts are > 0 (color + subtle animation) so admins notice immediately.
Optional: The Scheduled Safety Net (Approach C)

Events miss things like:

- Direct DB edits (manual SQL, migrations)
- Bulk imports that skip the service
- Stock changes made by a script that bypasses InventoryService

For that, add a nightly (or hourly) command:

// app/Console/Commands/CheckStockLevels.php

```php
public function handle(): void
{
    $recipients = User::whereIn('role', [...])->where('status', 'active')->get();

    $low = Product::query()
        ->where('track_inventory', true)
        ->where('current_stock', '>', 0)
        ->whereColumn('current_stock', '<=', 'low_stock_threshold')
        ->get();

    $out = Product::query()
        ->where('track_inventory', true)
        ->where('current_stock', '<=', 0)
        ->get();

    if ($low->isNotEmpty()) {
        Notification::send($recipients, new LowStockDigestNotification($low));
    }

    if ($out->isNotEmpty()) {
        Notification::send($recipients, new OutOfStockDigestNotification($out));
    }
}
```

Schedule it in Kernel::schedule():

```php
$schedule->command('stock:check-levels')->dailyAt('08:00');
```

Now you get:

- Real-time event when a single product crosses a threshold.
- Daily digest that catches anything the events missed, and reminds admins of the current state.

Preventing Notification Fatigue

Two rules that make a big difference:

1. Notify on transition, not on state. Never send "still low" or "still out". Only fire when the status changes.
2. Rate-limit per product. If a product oscillates around the threshold (sells 1, restocks 1, sells 1...), you'll spam. Add a cache check:

```php
protected function shouldNotify(Product $product, string $status): bool
{
    $key = "stock_notified:{$product->id}:{$status}";

    if (Cache::has($key)) {
        return false;
    }

    Cache::put($key, true, now()->addHours(12));

    return true;
}
```

Now at most one "low stock" notification per product per 12 hours. Tune the window to taste.

My Concrete Recommendation

For your current app:

- Add Product::stockStatus() — one source of truth.
- Dispatch StockLevelChanged from addStock / removeStock / updateStock — only when status actually changes.
- One listener — sends DB + mail notifications to admins.
- One daily command — sends a digest of currently low/out-of-stock products as a safety net.
- UI: keep your existing stat cards; highlight them when counts > 0.
- Skip the full notification-preferences system, per-user digests, and multi-channel batching for now. Add them when you actually have users complaining.

This gives you:

- Instant awareness when a sale pushes a product low.
- Daily reminder of everything currently low.
- No spam from oscillation.
- A single place (stockStatus()) that defines what "low" means.

That's the right amount of machinery for a POS app — enough to be reliable, not so much that it becomes its own maintenance burden.
