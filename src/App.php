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
use BaAGee\Event\Event;
use BaAGee\Log\Log;
use BaAGee\Log\LogLevel;
use BaAGee\MySQL\DBConfig;
use BaAGee\MySQL\SqlRecorder;
use BaAGee\NkNkn\Base\EventAbstract;
use BaAGee\NkNkn\Base\TimerTrait;
use BaAGee\NkNkn\Constant\CoreEventList;
use BaAGee\Wtf\Handler\WtfHandler;
use BaAGee\Wtf\WtfError;

/**
 * Class App
 * @package BaAGee\NkNkn
 */
class App
{
    use TimerTrait;
    /**
     * @var bool 是否初始化
     */
    protected static $isInit = false;

    /**
     * App constructor.
     * @throws \Exception
     */
    final public function __construct()
    {
        if (self::$isInit === false) {
            list(, $time) = self::executeTime(function () {
                // 设置本次请求的ID
                $this->setTraceId();
                // 配置初始化
                $this->configInit();
                if (Config::get('app/is_debug', true)) {
                    //开发模式及时 清空缓存
                    $this->removeCache(
                        AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR
                    );
                }
                //设置时区
                $this->setTimezone();
                // 注册错误提示
                $this->wtfInit();
                //数据库初始化
                $this->mysqlInit();
                // Log初始化
                $this->logInit();
                // 注册事件
                $this->registerEvents();
                self::$isInit = true;
            });
            Log::info(sprintf('App init time:%sms', $time));

            // 触发app初始化事件
            Event::trigger(CoreEventList::APP_AFTER_INIT_EVENT);
        }
    }

    /**
     * 注册事件
     */
    final protected function registerEvents()
    {
        $func   = function ($name, $event) {
            if (class_exists($event)) {
                if (is_subclass_of($event, EventAbstract::class)) {
                    $obj = new $event();
                    Event::listen($name, [$obj, 'main']);
                } else {
                    Log::warning($event . ' not extend ' . EventAbstract::class);
                }
            } else {
                Log::warning($event . ' event class not found');
            }
        };
        $events = Config::get('event', []);
        if (!empty($events)) {
            foreach ($events as $name => $event) {
                if (is_array($event)) {
                    foreach ($event as $e) {
                        $func($name, $e);
                    }
                } elseif (is_string($event)) {
                    $func($name, $event);
                }
            }
        }
    }

    /**
     * 设置时区
     */
    final private function setTimezone()
    {
        $tz = Config::get('app/timezone', '');
        if (!empty($tz)) {
            date_default_timezone_set($tz);
        }
    }

    /**
     * 设置请求ID
     */
    final private function setTraceId()
    {
        if (isset($_SERVER['HTTP_X_TRACE_ID']) && !empty($_SERVER['HTTP_X_TRACE_ID'])) {
            AppEnv::set('TRACE_ID', $_SERVER['HTTP_X_TRACE_ID']);
        } else {
            AppEnv::set('TRACE_ID', intval((microtime(true) * 10000)) . mt_rand(1000, 9999));
        }
    }

    /**
     * Log初始化
     * @throws \Exception
     */
    final private function logInit()
    {
        $logConfig = Config::get('log', []);
        if (!empty($logConfig)) {
            $formatter = empty($logConfig['formatter']) ? LogFormatter::class : $logConfig['formatter'];
            Log::init(new $logConfig['handler']($logConfig['handler_config']), $logConfig['cache_limit_percent'], $formatter);
            if (!Config::get('app/is_debug', true)) {
                //非开发调试模式隐藏部分Log提升性能
                LogLevel::setProductHiddenLevel((array)$logConfig['product_hidden_levels'] ?? []);
            }
        } else {
            throw new \Exception("没有log配置文件");
        }
    }

    /**
     * 配置初始化
     * @throws \Exception
     */
    final private function configInit()
    {
        // 配置初始化
        Config::init(AppEnv::get('CONFIG_PATH'), ParsePHPFile::class);
        if (!Config::get('app/is_debug', true)) {
            // 不是开发调试模式 更快的读取配置信息
            Config::fast(AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'cache');
        }
    }

    /**
     * 错误信息展示初始化
     * @throws \Exception
     */
    final private function wtfInit()
    {
        // 注册错误提示
        WtfError::register(new WtfHandler([
            'is_debug'             => Config::get('app/is_debug', true),#是否为调试模式
            #php error log路径不为空就调用写Log方法
            'php_error_log_dir'    => implode(DIRECTORY_SEPARATOR, [AppEnv::get('RUNTIME_PATH'), 'log']),
            'product_error_hidden' => Config::get('app/product_error_hidden', []),# 非调试模式下隐藏哪种PHP错误类型
            'dev_error_hidden'     => Config::get('app/debug_error_hidden', []),# 调试开发模式下隐藏哪种PHP错误类型
        ]));
    }

    /**
     * mysql配置初始化
     * @throws \Exception
     */
    final private function mysqlInit()
    {
        $dbConfig = Config::get('mysql', []);
        if (!empty($dbConfig)) {
            $dbConfig['schemasCachePath'] = implode(DIRECTORY_SEPARATOR, [
                AppEnv::get('RUNTIME_PATH'), 'cache', 'schemas'
            ]);
            // Db配置初始化
            DBConfig::init($dbConfig);
            // Sql记录到Log
            SqlRecorder::setSaveHandler(function ($params) {
                $totalTime   = number_format(
                    ($params['sqlInfo']['endTime'] - $params['sqlInfo']['startTime']) * 1000, 5
                );
                $connectTime = number_format(
                    ($params['sqlInfo']['connectedTime'] - $params['sqlInfo']['startTime']) * 1000, 5
                );
                $sqlTime     = number_format(
                    ($params['sqlInfo']['endTime'] - $params['sqlInfo']['connectedTime']) * 1000, 5
                );
                $logStr      = json_encode(array_merge([
                    'totalTime'   => $totalTime . 'ms',
                    'connectTime' => $connectTime . 'ms',
                    'sqlTime'     => $sqlTime . 'ms'
                ], $params['sqlInfo']), JSON_UNESCAPED_UNICODE);
                Log::debug($logStr);
            });
        }
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
     * 删除缓存
     * @param $path
     */
    final private function removeCache($path)
    {
        if (is_dir($path)) {
            $p = scandir($path);
            foreach ($p as $val) {
                if ($val != "." && $val != "..") {
                    if (is_dir($path . $val)) {
                        $path_ = $path . $val . DIRECTORY_SEPARATOR;
                        $this->removeCache($path_);
                        rmdir($path_);
                    } else {
                        unlink($path . $val);
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    final private function cgi()
    {
        // 路由初始化前
        Event::trigger(CoreEventList::ROUTER_BEFORE_INIT_EVENT);

        list(, $time) = self::executeTime(function () {
            if (Config::get('app/is_debug', true) ||
                Router::setCachePath(AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'cache') === false) {
                Router::init(include AppEnv::get('APP_PATH') . DIRECTORY_SEPARATOR . 'routes.php');
            }
            Router::setNotFound(function () {
                $file = Config::get('app/404file', '');
                if (is_file(AppEnv::get('ROOT_PATH') . DIRECTORY_SEPARATOR . 'public' . $file)) {
                    header('Location: ' . $file);
                } else {
                    http_response_code(404);
                }
            });
        });

        Log::info(sprintf('Router init time:%sms', $time));

        // 路由初始化后
        Event::trigger(CoreEventList::ROUTER_AFTER_INIT_EVENT);

        // 路由匹配开始前
        Event::trigger(CoreEventList::ROUTER_BEFORE_DISPATCH_EVENT);

        list($response, $time) = self::executeTime(Router::class . '::dispatch',
            0, $_SERVER['PATH_INFO'], $_SERVER['REQUEST_METHOD']);
        echo $response;
        Log::info(sprintf('Router dispatch and action run time:%sms', $time));

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * cli命令行程序入口
     * @param array $params
     */
    protected function cli($params)
    {

    }
}
