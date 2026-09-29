<?php

// Push notification texts (fallback; editable copies live in notification_templates).
// Keys are notification codes. Placeholders: :order_number, :store, :pin.
return [
    'ORDER_CONFIRMED' => [
        'title' => 'ご注文を受け付けました',
        'body' => ':store がご注文 :order_number を受け付けました。',
    ],
    'ORDER_COOKING' => [
        'title' => '調理中です',
        'body' => ':store がご注文 :order_number を調理しています。',
    ],
    'DRIVER_ASSIGNED' => [
        'title' => '配達員が決まりました',
        'body' => 'まもなく配達員がご注文 :order_number を受け取ります。',
    ],
    'ORDER_ON_THE_WAY' => [
        'title' => '配達中です',
        'body' => 'ご注文 :order_number を配達中です。受け取りPINは :pin です。',
    ],
    'DRIVER_ARRIVED' => [
        'title' => '配達員が到着しました',
        'body' => '配達員にPIN :pin をお伝えください。',
    ],
    'ORDER_DELIVERED' => [
        'title' => '配達が完了しました',
        'body' => 'ご注文 :order_number をお届けしました。お召し上がりください！',
    ],
    'ORDER_CANCELLED' => [
        'title' => 'ご注文がキャンセルされました',
        'body' => 'ご注文 :order_number はキャンセルされました。',
    ],
    'DELIVERY_FAILED' => [
        'title' => '配達できませんでした',
        'body' => 'ご注文 :order_number を配達できませんでした。店舗よりご連絡します。',
    ],
    'PAYMENT_RECEIVED' => [
        'title' => 'お支払いを確認しました',
        'body' => 'ご注文 :order_number のお支払いを確認しました。',
    ],
    'PAYMENT_FAILED' => [
        'title' => 'お支払いに失敗しました',
        'body' => 'ご注文 :order_number のお支払いが完了しませんでした。もう一度お試しください。',
    ],
    'DRIVER_NEW_DELIVERY' => [
        'title' => '新しい配達依頼',
        'body' => ':store で受け取り。アプリを開いて受けてください。',
    ],
];
