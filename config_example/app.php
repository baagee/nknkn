<?php

use BaAGee\Log\Handler\FileLog;
use BaAGee\NkNkn\AppEnv;
use \BaAGee\NkNkn\LogFormatter;

return [
    'app_name'             => 'app_name',
    'timezone'             => 'PRC',
    // Log缓存占用php.ini限制的内存百分比
    '404file'              => '/assets/404.html',//404时页面文件路径
    'is_debug'             => true,//是否开发调试模式
    'product_error_hidden' => [E_WARNING, E_NOTICE, E_STRICT, E_DEPRECATED],# 非调试模式下隐藏哪种PHP错误类型
    'debug_error_hidden'   => [E_WARNING, E_NOTICE, E_STRICT, E_DEPRECATED],# 调试开发模式下隐藏哪种PHP错误类型
    'sync_task'            => [
        // 异步任务文件锁路径
        'lock_file' => \BaAGee\NkNkn\AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'sync_task_lock',
        // 同时最大任务进程数
        'max_task'  => 50,
    ]
];
