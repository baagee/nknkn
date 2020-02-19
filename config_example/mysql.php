<?php

use BaAGee\NkNkn\AppEnv;

return [
    'host'           => '127.0.0.1',
    'port'           => 5200,
    'user'           => 'ppp',
    'password'       => 'password',
    'database'       => 'db',
    'connectTimeout' => 1,
    'charset'        => 'utf8mb4',
    'slave'          => [
        [
            'host'           => '127.0.0.1',
            'port'           => 5200,
            'user'           => 'ppp',
            'password'       => 'password',
            'database'       => 'db',
            'connectTimeout' => 1,
            'charset'        => 'utf8mb4',
            'weight'         => 3,//数据库从库权重，越大使用的频率越大
        ],
        [
            'host'           => '127.0.0.1',
            'port'           => 5200,
            'user'           => 'ppp',
            'password'       => 'password',
            'database'       => 'db',
            'connectTimeout' => 1,
            'charset'        => 'utf8mb4',
            'weight'         => 5,//数据库从库权重，越大使用的频率越大
        ]
    ]
];

