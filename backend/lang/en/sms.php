<?php

// Keep SMS short: cost is per 160 (GSM-7) / 70 (UCS-2) character segment.
// Keys: 'otp' and lower-cased notification codes (see NotificationService).
return [
    'otp' => 'Malawi Bento code: :code. Valid for :minutes min. Do not share it.',
    'order_confirmed' => 'Malawi Bento: Order :order_number confirmed.',
    'order_on_the_way' => 'Malawi Bento: Order :order_number is on the way. PIN: :pin',
    'driver_arrived' => 'Malawi Bento: Your rider has arrived. PIN: :pin',
];
