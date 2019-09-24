<?php
/**
 * Desc: App
 * User: baagee
 * Date: 2019/3/29
 * Time: 下午10:57
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\Config\Parser\ParsePHPFile;
use BaAGee\Log\Log;
use BaAGee\MySQL\DBConfig;
use BaAGee\MySQL\SqlRecorder;
use BaAGee\Wtf\Handler\WtfHandler;
use BaAGee\Wtf\WtfError;

/**
 * Class App
 * @package BaAGee\NkNkn
 */
class App
{
    /**
     * App constructor.
     * @throws \Exception
     */
    final public function __construct()
    {
        $startInitTime = microtime(true);
        // 设置本次请求的ID
        AppEnv::set('TRACE_ID', (microtime(true) * 10000) . mt_rand(1000, 9999));

        // 配置初始化
        Config::init(AppEnv::get('CONFIG_PATH'), ParsePHPFile::class);
        // 注册错误提示
        WtfError::register(new WtfHandler([
            'is_debug'             => Config::get('app/is_debug'),#是否为调试模式
            #php error log路径不为空就调用写Log方法
            'php_error_log_dir'    => implode(DIRECTORY_SEPARATOR, [AppEnv::get('RUNTIME_PATH'), 'log']),
            'product_error_hidden' => [E_WARNING, E_NOTICE, E_STRICT, E_DEPRECATED],# 非调试模式下隐藏哪种PHP错误类型
            'dev_error_hidden'     => [E_WARNING, E_NOTICE, E_STRICT, E_DEPRECATED],# 调试开发模式下隐藏哪种PHP错误类型
        ]));

        $dbConfig = Config::get('mysql');
        if (!empty($dbConfig)) {
            // Db配置初始化
            DBConfig::init($dbConfig);
            // Sql记录到Log
            SqlRecorder::setSaveHandler(function ($params) {
                $totalTime   = number_format(($params['sqlInfo']['endTime'] - $params['sqlInfo']['startTime']) * 1000, 5);
                $connectTime = number_format(($params['sqlInfo']['connectedTime'] - $params['sqlInfo']['startTime']) * 1000, 5);
                $sqlTime     = number_format(($params['sqlInfo']['endTime'] - $params['sqlInfo']['connectedTime']) * 1000, 5);
                $logStr      = json_encode(array_merge([
                    'totalTime'   => $totalTime . 'ms',
                    'connectTime' => $connectTime . 'ms',
                    'sqlTime'     => $sqlTime . 'ms'
                ], $params['sqlInfo']), JSON_UNESCAPED_UNICODE);
                Log::debug($logStr);
            });
        }

        // Log初始化
        $logHandler = Config::get('app/log');
        $formatter  = empty($logHandler['formatter']) ? LogFormatter::class : $logHandler['formatter'];
        Log::init(new $logHandler['handler']($logHandler['handler_config']), $logHandler['cache_limit_percent'], $formatter);
        Log::info('app init');
        $endInitTime = microtime(true);
        Log::info(sprintf('%s time:%sms', __METHOD__, ($endInitTime - $startInitTime) * 1000));
    }

    /**
     * @throws \Exception
     */
    final public function run()
    {
        if (PHP_SAPI !== 'cli') {
            $this->cgi();
        } else {
            // 命令行下
            $this->cli($argv ?? []);
        }
    }

    /**
     * @throws \Exception
     */
    final protected function cgi()
    {
        $routerStartTime = microtime(true);
        if (AppEnv::get('IS_DEBUG') ||
            Router::setCachePath(AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'cache') === false) {
            Log::info('router init');
            Router::batchAdd(include_once AppEnv::get('APP_PATH') . DIRECTORY_SEPARATOR . 'routes.php');
        }
        Router::setNotFound(function () {
            $file = Config::get('app/404file');
            if (is_file(AppEnv::get('ROOT_PATH') . DIRECTORY_SEPARATOR . 'public' . $file)) {
                header('Location: ' . $file);
            } else {
                http_response_code(404);
            }
        });
        $routerEndTime = microtime(true);
        Log::info(sprintf('App init time:%sms', ($routerEndTime - $routerStartTime) * 1000));
        echo Router::dispatch();
        $dispatchEndTime = microtime(true);
        Log::info(sprintf('App dispatch time:%sms', ($dispatchEndTime - $routerEndTime) * 1000));
    }

    /**
     * cli命令行程序入口
     * @param array $params
     */
    protected function cli($params)
    {

    }
}
