<?php

return [
    'token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'api_version' => env('WHATSAPP_API_VERSION'),
    'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),
    'reminder_time' => env('WHATSAPP_REMINDER_TIME', '09:00'),
];
