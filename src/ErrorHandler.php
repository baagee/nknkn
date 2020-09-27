<?php
/**
 * Desc: 自定义错误处理
 * User: baagee
 * Date: 2020/9/27
 * Time: 下午7:38
 */

namespace BaAGee\NkNkn;

use BaAGee\Wtf\Handler\WtfHandler;

class ErrorHandler extends WtfHandler
{
    /**
     * 写入php error log
     * @param \Throwable $t
     */
    public function writePhpErrorLog(\Throwable $t)
    {
        $log_str = sprintf('[%s] [%d] %s %s: %s File:%s:%d Trace:%s' . PHP_EOL,
            date('Y-m-d H:i:s'), $t->getCode(), AppEnv::get('TRACE_ID'), $this->errorType, $t->getMessage(),
            $t->getFile(), $t->getLine(), str_replace(PHP_EOL, '  ', $t->getTraceAsString())
        );
        if (!is_dir($this->conf['php_error_log_dir'])) {
            mkdir($this->conf['php_error_log_dir'], 0755, true);
        }
        error_log($log_str, 3, $this->conf['php_error_log_dir'] . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log');
    }
}