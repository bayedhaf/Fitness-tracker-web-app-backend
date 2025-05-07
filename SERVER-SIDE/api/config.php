<?php
// config.php
return [
    'email' => [
        'host' => 'smtp.yourdomain.com',
        'username' => 'contact@yourdomain.com',
        'password' => 'your-secure-password',
        'port' => 587,
        'encryption' => 'tls'
    ],
    'recaptcha' => [
        'secret_key' => 'your-recaptcha-secret'
    ]
];
?>