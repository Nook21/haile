<?php
// Simple loader for manually installed PHPMailer
// If you run `composer install` later, replace this with: require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $map = [
        'PHPMailer\\PHPMailer\\PHPMailer'  => __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        'PHPMailer\\PHPMailer\\SMTP'       => __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php',
        'PHPMailer\\PHPMailer\\Exception'  => __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php',
    ];
    if (isset($map[$class])) require_once $map[$class];
});
