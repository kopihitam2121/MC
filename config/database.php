<?php
declare(strict_types=1);

return [
    'host' => mc_env('MC_DB_HOST', '127.0.0.1'),
    'port' => mc_env('MC_DB_PORT', '3306'),
    'name' => mc_env('MC_DB_NAME', 'money_changer'),
    'user' => mc_env('MC_DB_USER', 'root'),
    'pass' => mc_env('MC_DB_PASS', ''),
];
