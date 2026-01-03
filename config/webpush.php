<?php
return [
    'vapid' => [
        'subject' => 'mailto:fallenboy111@gmail.com',
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],
];