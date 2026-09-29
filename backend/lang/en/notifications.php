<?php

// Push notification texts (fallback; editable copies live in notification_templates).
// Keys are notification codes. Placeholders: :order_number, :store, :pin.
return [
    'ORDER_CONFIRMED' => [
        'title' => 'Order confirmed',
        'body' => 'Your order :order_number has been accepted by :store.',
    ],
    'ORDER_COOKING' => [
        'title' => 'Preparing your food',
        'body' => ':store is now preparing order :order_number.',
    ],
    'DRIVER_ASSIGNED' => [
        'title' => 'Rider assigned',
        'body' => 'A rider will pick up order :order_number soon.',
    ],
    'ORDER_ON_THE_WAY' => [
        'title' => 'On the way',
        'body' => 'Order :order_number is on the way. Your delivery PIN is :pin.',
    ],
    'DRIVER_ARRIVED' => [
        'title' => 'Your rider has arrived',
        'body' => 'Please meet the rider and tell them your PIN: :pin.',
    ],
    'ORDER_DELIVERED' => [
        'title' => 'Delivered',
        'body' => 'Order :order_number has been delivered. Enjoy your meal!',
    ],
    'ORDER_CANCELLED' => [
        'title' => 'Order cancelled',
        'body' => 'Order :order_number has been cancelled.',
    ],
    'DELIVERY_FAILED' => [
        'title' => 'Delivery failed',
        'body' => 'We could not deliver order :order_number. The store will contact you.',
    ],
    'PAYMENT_RECEIVED' => [
        'title' => 'Payment received',
        'body' => 'We received the payment for order :order_number.',
    ],
    'PAYMENT_FAILED' => [
        'title' => 'Payment failed',
        'body' => 'The payment for order :order_number did not go through. Please try again.',
    ],
    'DRIVER_NEW_DELIVERY' => [
        'title' => 'New delivery request',
        'body' => 'Pick-up at :store. Open the app to accept.',
    ],
];
