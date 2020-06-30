<?php

return [
    'enable'     => false,//是否使用Cookie
    'prefix'     => '', // cookie 名称前缀
    'expire'     => 3600 * 12, // cookie 保存时间
    'path'       => '/', // cookie 保存路径
    'domain'     => '', // cookie 有效域名
    'secure'     => false, //  cookie 启用安全传输
    'httponly'   => true, // httponly 设置
    'setcookie'  => true, // 是否使用 setcookie
];
