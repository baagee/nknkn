<?php
return [
    'host'               => '127.0.0.1:8550',
    'timeout_ms'         => 1000,//读取超时 毫秒
    'connect_timeout_ms' => 1000, // 连接超时 毫秒
    'max_redirs'         => 1,
    // 代理设置
    'proxy'              => [
        'ip'   => '',
        'port' => ''
    ],
    'referer'            => '',// http-referer
    'user_agent'         => '',// user-agent
    'return_header'      => 0,//返回值是否展示header
    'retry_times'        => 1,//单个请求时失败重试次数
];