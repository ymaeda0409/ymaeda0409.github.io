<?php

// Push notification texts (fallback; editable copies live in notification_templates).
// Keys are notification codes. Placeholders: :order_number, :store, :pin.
return [
    'ORDER_CONFIRMED' => [
        'title' => 'Oda yalandiridwa',
        'body' => 'Oda yanu :order_number yalandiridwa ndi :store.',
    ],
    'ORDER_COOKING' => [
        'title' => 'Chakudya chikukonzedwa',
        'body' => ':store ikukonza oda :order_number.',
    ],
    'DRIVER_ASSIGNED' => [
        'title' => 'Wobweretsa wapezeka',
        'body' => 'Wobweretsa atenga oda :order_number posachedwa.',
    ],
    'ORDER_ON_THE_WAY' => [
        'title' => 'Ili pa njira',
        'body' => 'Oda :order_number ili pa njira. PIN yanu ndi :pin.',
    ],
    'DRIVER_ARRIVED' => [
        'title' => 'Wobweretsa wafika',
        'body' => 'Chonde kumanani ndi wobweretsa ndipo muuzeni PIN yanu: :pin.',
    ],
    'ORDER_DELIVERED' => [
        'title' => 'Zaperekedwa',
        'body' => 'Oda :order_number yaperekedwa. Sangalalani ndi chakudya!',
    ],
    'ORDER_CANCELLED' => [
        'title' => 'Oda yathetsedwa',
        'body' => 'Oda :order_number yathetsedwa.',
    ],
    'DELIVERY_FAILED' => [
        'title' => 'Kubweretsa sikunatheke',
        'body' => 'Sitinathe kubweretsa oda :order_number. Sitolo ikulumikizanani.',
    ],
    'PAYMENT_RECEIVED' => [
        'title' => 'Malipiro alandiridwa',
        'body' => 'Talandira malipiro a oda :order_number.',
    ],
    'PAYMENT_FAILED' => [
        'title' => 'Kulipira sikunatheke',
        'body' => 'Malipiro a oda :order_number sanadutse. Chonde yesaninso.',
    ],
    'DRIVER_NEW_DELIVERY' => [
        'title' => 'Pempho latsopano',
        'body' => 'Katengeni ku :store. Tsegulani pulogalamu kuti mulandire.',
    ],
];
