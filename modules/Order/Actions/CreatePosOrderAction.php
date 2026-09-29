<?php

namespace Modules\Order\Actions;

use Modules\Order\Enums\DeliveryStatusEnum;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;
use Modules\User\Models\User;

class CreatePosOrderAction
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    /**
     * Create an order originating from the POS channel.
     *
     * Responsibilities:
     *  - Translate POS-shaped validated input into the normalized
     *    shape expected by OrderService::create().
     *  - Apply POS-specific defaults (walk-in customer, shop pickup,
     *    immediate inventory deduction).
     */
    public function execute(array $validated, User $cashier): Order
    {
        $data = [
            'order_channel' => $validated['order_channel'],

            'customer' => [
                'name' => $validated['customer_name'] ?: 'Walk-in',
                'phone' => $validated['customer_phone'] ?: null,
                'email' => $validated['customer_email'] ?: null,
            ],

            'delivery' => [
                'method' => $validated['delivery_method'],
                'cost' => (float) $validated['delivery_cost'],
                'location' => 'shop',   // POS: always shop
                'area' => 'shop',
                'address' => 'shop',
                'status' => DeliveryStatusEnum::PICKED_UP->value,
            ],

            'cart_items' => $validated['cart_items'],
            'payments' => $validated['payments'],

            'user_id' => $validated['user_id'] ?? null,

            'initial_order_status' => $validated['delivery_method'] === 'delivery'
                ? OrderStatusEnum::PENDING->value
                : OrderStatusEnum::READY_FOR_PICKUP->value,

            'deduct_inventory' => true,   // POS: item leaves the store now
        ];

        return $this->orderService->create($data, $cashier);
    }
}