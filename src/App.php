<?php
/**
 * Desc: App
 * User: baagee
 * Date: 2019/3/29
 * Time: 下午10:57
 */

namespace BaAGee\NkNkn;

use BaAGee\AsyncTask\TaskBase;
use BaAGee\AsyncTask\TaskScheduler;
use BaAGee\Config\Config;
use BaAGee\Config\Parser\PhpParser;
use BaAGee\DebugTrace\DebugTrace;
use BaAGee\DebugTrace\OutputHtml;
use BaAGee\DebugTrace\TraceCollector;
use BaAGee\Event\Event;
use BaAGee\Log\Log;
use BaAGee\Log\LogLevel;
use BaAGee\MySQL\DBConfig;
use BaAGee\MySQL\SqlRecorder;
use BaAGee\NkNkn\Base\EventAbstract;
use BaAGee\NkNkn\Base\TraitFunc\TimerTrait;
use BaAGee\Wtf\Handler\WtfHandler;
use BaAGee\Wtf\WtfError;

/**
 * Class App
 * @package BaAGee\NkNkn
 */
abstract class App extends TaskBase
{
    const APP_AFTER_INIT_EVENT = 'CORE_APP_AFTER_INIT_EVENT';

    use TimerTrait;

    /**
     * @var bool 是否初始化
     */
    protected static $isInit = false;

    /**
     * App初始化
     * @throws \Exception
     */
    final public static function init()
    {
        if (self::$isInit === false) {
            list(, $time) = self::executeTime(function () {
                // 设置本次请求的ID
                self::setTraceId();
                // 配置初始化
                self::configInit();
                //初始化调试信息页面输出
                self::initDebugTrace();
                //设置时区
                self::setTimezone();
                // 注册错误提示
                self::wtfInit();
                //数据库初始化
                self::mysqlInit();
                // Log初始化
                self::logInit();
                // 注册事件
                self::registerEvents();
                // 异步任务
                self::asyncTaskInit();
                self::$isInit = true;
            });
            Log::info(sprintf('App init time:%sms', $time));

            // 触发app初始化事件
            Event::trigger(self::APP_AFTER_INIT_EVENT);
        }
    }

    /**
     * 初始化页面调试信息
     */
    final private static function initDebugTrace()
    {
        if (PHP_SAPI != 'cli' && Config::get('app/is_debug', true)) {
            $class = Config::get('app/debug_trace_output', OutputHtml::class);
            DebugTrace::init($class);
            TraceCollector::setEnv(AppEnv::getAll());
            Log::listenOnWrite(function ($level, $logArr) {
                $arr = explode('.', number_format($logArr['time'], 4, '.', ''));
                $time = date('H:i:s', $arr[0]) . '.' . ($arr[1] ?? '0000');
                TraceCollector::addLog(TraceCollector::TRACE_TYPE_LOG, sprintf('[%s][%s][%s:%d] %s', $level, $time, $logArr['file'], $logArr['line'], $logArr['log']));
            });
        }
    }

    /**
     * App constructor.
     * @throws \Exception
     */
    final public function __construct()
    {
        self::init();
    }

    /**
     * 注册事件
     */
    final private static function registerEvents()
    {
        $func = function ($name, $event) {
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
    final private static function setTimezone()
    {
        $tz = Config::get('app/timezone', '');
        if (!empty($tz)) {
            date_default_timezone_set($tz);
        }
    }

    /**
     * 设置请求ID
     */
    final private static function setTraceId()
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
    final private static function logInit()
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
     * 异步任务模块初始化
     */
    final private static function asyncTaskInit()
    {
        $taskConfig = Config::get('app/async_task', []);
        $maxTask = intval($taskConfig['max_task'] ?? 10);
        $maxTask = $maxTask <= 0 ? 10 : $maxTask;
        $lockFile = $taskConfig['lock_file'] ?? AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'task_lock';
        $taskOutput = $taskConfig['output_dir'] ?? AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'task_output';
        TaskScheduler::init($lockFile, $maxTask, $taskOutput);
    }

    /**
     * 配置初始化
     * @throws \Exception
     */
    final private static function configInit()
    {
        // 配置初始化
        Config::init(AppEnv::get('CONFIG_PATH'), PhpParser::class);
        $cachePath = AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'cache';
        if (!Config::get('app/is_debug', true)) {
            // 不是开发调试模式 更快的读取配置信息
            Config::fast($cachePath);
        } else {
            //开发模式及时 清空缓存
            self::removeCache($cachePath);
        }
    }

    /**
     * 错误信息展示初始化
     * @throws \Exception
     */
    final private static function wtfInit()
    {
        // 注册错误提示
        WtfError::register(new WtfHandler([
            'is_debug' => Config::get('app/is_debug', true),#是否为调试模式
            # php error log路径不为空就调用写Log方法
            'php_error_log_dir' => implode(DIRECTORY_SEPARATOR, [AppEnv::get('RUNTIME_PATH'), 'log']),
            'product_error_hidden' => Config::get('app/product_error_hidden', []),# 非调试模式下隐藏哪种PHP错误类型
            'dev_error_hidden' => Config::get('app/debug_error_hidden', []),# 调试开发模式下隐藏哪种PHP错误类型
        ]));
    }

    /**
     * mysql配置初始化
     * @throws \Exception
     */
    final private static function mysqlInit()
    {
        $dbConfig = Config::get('mysql', []);
        if (!empty($dbConfig)) {
            $dbConfig['schemas_cache_path'] = implode(DIRECTORY_SEPARATOR, [
                AppEnv::get('RUNTIME_PATH'), 'cache', 'schemas'
            ]);
            // Db配置初始化
            DBConfig::init($dbConfig);
            // Sql记录到Log
            SqlRecorder::setSaveHandler(function ($params) {
                $totalTime = number_format(
                    ($params['sqlInfo']['endTime'] - $params['sqlInfo']['startTime']) * 1000, 5, '.', ''
                );
                $connectTime = number_format(
                    ($params['sqlInfo']['connectedTime'] - $params['sqlInfo']['startTime']) * 1000, 5, '.', ''
                );
                $sqlTime = number_format(
                    ($params['sqlInfo']['endTime'] - $params['sqlInfo']['connectedTime']) * 1000, 5, '.', ''
                );
                $logStr = json_encode(array_merge([
                    'totalTime' => $totalTime . 'ms',
                    'connectTime' => $connectTime . 'ms',
                    'sqlTime' => $sqlTime . 'ms'
                ], (array)$params['sqlInfo']), JSON_UNESCAPED_UNICODE);
                Log::debug($logStr);
            });
        }
    }

    /**
     * 删除缓存
     * @param $path
     */
    final private static function removeCache($path)
    {
        if (is_dir($path)) {
            $p = scandir($path);
            foreach ($p as $val) {
                if ($val != "." && $val != "..") {
                    $file = $path . DIRECTORY_SEPARATOR . $val;
                    if (is_dir($file)) {
                        self::removeCache($file);
                        rmdir($file);
                    } else {
                        unlink($file);
                    }
                }
            }
        }
    }
}
