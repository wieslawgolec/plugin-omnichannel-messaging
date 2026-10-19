<?php

declare(strict_types=1);

// Minimal stubs for Symfony HttpFoundation (controllers only need Request/Response)
require_once __DIR__ . '/Stub/Symfony/Component/HttpFoundation/Request.php';
require_once __DIR__ . '/Stub/Symfony/Component/HttpFoundation/Response.php';

// PSR-4 autoloader for the plugin
spl_autoload_register(static function (string $class): void {
    $prefix = 'MauticPlugin\\MauticOmnichannelMessagingBundle\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
