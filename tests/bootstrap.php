<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

DG\BypassFinals::enable();

// Force APP_ENV=test before loading .env files.
// This is required when the process environment (e.g. Docker compose) has APP_ENV=dev
// set as an OS environment variable, which would otherwise cause bootEnv to load the
// dev configuration instead of the test configuration.
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}
