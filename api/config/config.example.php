<?php

return [
    'admin' => [
        // Simple protection for the admin UI: /Landing/api/admin/?key=TU_CLAVE
        // Set a long random string.
        // Use a URL-safe string (avoid #, %, spaces) so it works without encoding.
        'key' => 'Jucatesa_9843_nEdoOpeMla_2024',
    ],

    // Where to persist leads
    'storage' => [
        'leads_file' => __DIR__ . '/../storage/leads.jsonl',
        'rate_limit_file' => __DIR__ . '/../storage/ratelimit.json',
    ],

    // Basic limits per IP
    'rate_limit' => [
        'per_minute' => 5,
        'per_hour' => 30,
    ],

    // Email settings (optional)
    // NOTE: `mail()` is unreliable on many Windows setups. If you want robust delivery,
    // we can switch to SMTP (PHPMailer) later.
    'email' => [
        'enabled' => false,
        'to' => 'luminacrm@gmail.com',
        'from' => 'no-reply@localhost',
        'subject' => 'Nueva solicitud de demo — Lumina CRM',
    ],

    // CORS
    // Allowing `*` helps when opening the landing via file:// during development.
    'cors' => [
        'allow_origin' => '*',
    ],
];
