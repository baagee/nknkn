<?php

return [
    'enable'       => true,//是否使用session
    'handler'      => '',//使用默认文件储存
    // 'handler'      => \BaAGee\Session\Handler\Redis::class,//使用redis储存
    // 'select'       => 2, // 操作库
    'expire'       => 3600 * 12, // 有效期(秒)
    'session_name' => 'session_', // sessionkey前缀
    'auto_start'   => 1,// 是否自动开启session
    'use_cookies'  => 1
];
