<?php

return [
    'host' => getenv('HIMS_SMTP_HOST') ?: '',
    'port' => (int) (getenv('HIMS_SMTP_PORT') ?: 587),
    'username' => getenv('HIMS_SMTP_USER') ?: '',
    'password' => getenv('HIMS_SMTP_PASSWORD') ?: '',
    'encryption' => strtolower(getenv('HIMS_SMTP_ENCRYPTION') ?: 'tls'),
    'from_email' => getenv('HIMS_SMTP_FROM') ?: '',
    'from_name' => getenv('HIMS_SMTP_FROM_NAME') ?: 'Hardware Inventory Management System',
    'app_url' => rtrim(getenv('HIMS_APP_URL') ?: '', '/')
];
