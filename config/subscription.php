<?php

return [
    'enabled' => env('SUBSCRIPTION_ENABLED', false),
    'webhook_secret' => env('PLANKA_WEBHOOK_SECRET'),
    'auto_register' => env('PLANKA_WEBHOOK_AUTO_REGISTER', false),
];
