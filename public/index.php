<?php

// TEMPORARY DEBUGGING - Force raw PHP error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__).'/var/log/php_errors.log');
error_log("--- PHP execution started in index.php ---");

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
