<?php

return [
    'host' => '127.0.0.1',
    'port' => 5200,
    'user' => 'ppp',
    'password' => 'password',
    'database' => 'db',
    'connectTimeout' => 1,
    'charset' => 'utf8mb4',
    'schemasCachePath' => \BaAGee\NkNkn\AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'schemas',
];
