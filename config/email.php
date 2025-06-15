<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'armyb4810@gmail.com');
define('SMTP_PASSWORD', 'cidy clgx glsz hffn');
define('SMTP_FROM_EMAIL', 'armyb4810@gmail.com');
define('SMTP_FROM_NAME', 'KoraTickets');

define('SMTP_DEBUG', true);  // Activer le débogage SMTP
define('SMTP_VERIFY_PEER', false);
define('SMTP_VERIFY_PEER_NAME', false);
define('SMTP_ALLOW_SELF_SIGNED', true);

return [
    'smtp' => [
        'host' => SMTP_HOST,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'port' => SMTP_PORT,
        'secure' => 'tls'
    ],
    'from' => [
        'address' => SMTP_FROM_EMAIL,
        'name' => SMTP_FROM_NAME
    ]
];
