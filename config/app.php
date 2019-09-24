<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/30
 * Time: 上午12:38
 */

use BaAGee\Log\Handler\FileLog;
use BaAGee\NkNkn\AppEnv;
use \BaAGee\NkNkn\LogFormatter;

return [
    'app_name' => 'app_name',
    // Log缓存占用php.ini限制的内存百分比
    '404file'  => '/assets/404.html',//404时页面文件路径
    'is_debug' => true,//是否开发调试模式
    'log'      => [
        //日志储存配置
        'handler'             => FileLog::class,//文件储存
        // 文件储存配置
        'handler_config'      => [
            // 基本目录
            'baseLogPath'   => implode(DIRECTORY_SEPARATOR, [AppEnv::get('RUNTIME_PATH'), 'log']),
            // 是否按照小时分割
            'autoSplitHour' => true,
            'subDir'        => 'app_name',
        ],
        'cache_limit_percent' => 5,// 缓存Log占用memory_limit百分比
        'formatter'           => LogFormatter::class
    ],
    // 有session配置就开始cookie
    'cookie'   => [
        'prefix'     => '', // cookie 名称前缀
        'expire'     => 3600 * 12, // cookie 保存时间
        'path'       => '/', // cookie 保存路径
        'domain'     => '', // cookie 有效域名
        'secure'     => false, //  cookie 启用安全传输
        'httponly'   => true, // httponly 设置
        'setcookie'  => true, // 是否使用 setcookie
        'encryptkey' => '798y567R%^E$RF87t78e123iJKBUyr765r',//是否加密，有值cookie就加密
    ],
    // 有session配置就开启session
    'session'  => [
        'handler'      => \BaAGee\Session\Handler\Redis::class,//使用redis储存
        'host'         => '127.0.0.1', // redis主机
        'port'         => 6379, // redis端口
        'password'     => '', // 密码
        'select'       => 2, // 操作库
        'expire'       => 3600 * 12, // 有效期(秒)
        'timeout'      => 0, // 超时时间(秒)
        'persistent'   => false, // 是否长连接
        'session_name' => 'session_', // sessionkey前缀
        'auto_start'   => 1,// 是否自动开启session
        'use_cookies'  => 1
    ],
];