<?php

if (count($argv) < 2) {
    echo '请输入项目根目录' . PHP_EOL;
    die();
}

$rootPath = realpath($argv[1]);
$appName = $argv[2] ?? 'app';
$composerFile = $rootPath . '/composer.json';
if (!is_file($composerFile)) {
    echo "项目根目录没有composer.json文件" . PHP_EOL;
    die;
}

$dirs = [
    'app' => [
        'Action', 'Controller', 'Event', 'Library', 'Middleware', 'Model', 'Script', 'View'
    ],
    'config', 'runtime', 'public'
];


foreach ($dirs as $dir => $subDirs) {
    if ($dir === 'app') {
        $path = $rootPath . DIRECTORY_SEPARATOR . $dir;
        if (!is_dir($path)) {
            $res = mkdir($path, 0755, true);
            echo '创建文件夹：' . (str_replace($rootPath . '/', '', $path)) . ' ' . ($res ? '成功' : '失败') . PHP_EOL;
        }
        foreach ($subDirs as $subDir) {
            $path = $rootPath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $subDir;
            if (!is_dir($path)) {
                $res = mkdir($path, 0755, true);
                echo '创建文件夹：' . (str_replace($rootPath . '/', '', $path)) . ' ' . ($res ? '成功' : '失败') . PHP_EOL;
            }
        }
    } else {
        $path = $rootPath . DIRECTORY_SEPARATOR . $subDirs;
        if (!is_dir($path)) {
            $res = mkdir($path, 0755, true);
            echo '创建文件夹：' . (str_replace($rootPath . '/', '', $path)) . ' ' . ($res ? '成功' : '失败') . PHP_EOL;
        }
        if ($subDirs === 'runtime') {
            chmod($path, 0775);
        }
    }
}

$indexFile = <<<CODE
<?php

include_once __DIR__ . '/../vendor/autoload.php';

\$app = new \BaAGee\NkNkn\App();
\$app->run();

CODE;

$index = $rootPath . '/public/index.php';
if (is_file($index)) {
    echo 'public/index.php 文件已存在，已忽略' . PHP_EOL;
} else {
    file_put_contents($index, $indexFile);
    chmod($index, 0755);
    echo 'public/index.php 创建成功' . PHP_EOL;
}


$composerContent = trim(file_get_contents($composerFile));
$comArr = json_decode($composerContent, true) ?? [];
$comArr['autoload']['psr-4']['App\\'] = 'app/';
file_put_contents($composerFile, json_encode($comArr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo 'composer.json 添加自动加载命名空间App' . PHP_EOL;

$configExamples = $rootPath . '/vendor/baagee/nknkn/config_example';
$configPath = $rootPath . '/config';


function copyFile($fileOrDir, $destDir)
{
    global $rootPath;
    $ss = scandir($fileOrDir);
    foreach ($ss as $s) {
        if (!in_array($s, ['.', '..'])) {
            $file = $fileOrDir . '/' . $s;
            if (is_file($file)) {
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                if (is_file($destDir . '/' . $s)) {
                    echo str_replace($rootPath . '/', '', $destDir . '/' . $s) . ' 文件已存在，已忽略' . PHP_EOL;
                } else {
                    copy($file, $destDir . '/' . $s);
                }
            } elseif (is_dir($file)) {
                copyFile($file, $destDir . '/' . $s);
            }
        }
    }
}

function randomString($len = 10)
{
    $a = 'qwertyuiop[]asdfghjkl;/.,mnbvcxz1234567890-=+_)(*&^%$#@!~`';
    $l = strlen($a) - 1;
    $ret = '';
    for ($i = 1; $i <= $len; $i++) {
        $ret .= $a[mt_rand(0, $l)];
    }
    return $ret;
}

copyFile($configExamples, $configPath);
$_code = file_get_contents($configPath . '/cookie.php');
$_code = str_replace("{{encryptkey}}", randomString(50), $_code);
file_put_contents($configPath . '/cookie.php', $_code);

$_code = file_get_contents($configPath . '/app.php');
$_code = str_replace("{{app_name}}", $appName, $_code);
file_put_contents($configPath . '/app.php', $_code);

$routesFile = $rootPath . '/app/routes.php';
$routesContent = <<<CODE
<?php
// 路由文件
return [
    '/api/hello' => [
        'method'     => ['post', 'get', 'delete', 'put', 'options', 'patch', 'head'],
        'callback'   => \App\Action\Test\Hello::class,//直接指定到action类
        'middleware' => [
            // 中间件列表
            \App\Middleware\ReturnJson::class
        ]
    ],
];

CODE;
if (is_file($routesFile)) {
    echo 'app/routes.php 已经存在，已忽略' . PHP_EOL;
} else {
    file_put_contents($routesFile, $routesContent);
    echo 'app/routes.php 创建成功' . PHP_EOL;
}

$returnJsonContent = <<<CODE
<?php
// 自动输出json的中间件
namespace App\Middleware;

use BaAGee\Log\Log;
use BaAGee\NkNkn\Base\MiddlewareAbstract;
use BaAGee\NkNkn\Constant\CoreNoticeCode;
use BaAGee\NkNkn\UserNotice;

class ReturnJson extends MiddlewareAbstract
{
    protected function handler(\Closure \$next, \$data)
    {
        try {
            \$res = \$next(\$data);
        } catch (UserNotice \$e) {
            \$errMsg  = \$e->getMessage();
            \$errCode = \$e->getCode();
            \$res     = \$e->getErrorData() ?? '';
            Log::warning(sprintf('逻辑异常：[%d] %s', \$errCode, \$errMsg));
        } catch (\Exception \$e) {
            \$errCode = CoreNoticeCode::DEFAULT_ERROR_CODE;
            \$errMsg  = '系统异常~';
            Log::warning(sprintf('系统异常：[%d] %s', \$e->getCode(), \$e->getMessage()));
        }
        header('Content-Type: application/json; charset=utf-8');
        return json_encode([
            'code'    => \$errCode ?? 0,
            'message' => \$errMsg ?? '',
            'data'    => \$res ?? '',
        ], JSON_UNESCAPED_UNICODE);
    }
}

CODE;
$returnJsonFile = $rootPath . '/app/Middleware/ReturnJson.php';
if (is_file($returnJsonFile)) {
    echo 'app/Middleware/ReturnJson.php 已经存在，已忽略' . PHP_EOL;
} else {
    file_put_contents($returnJsonFile, $returnJsonContent);
    echo 'app/Middleware/ReturnJson.php 创建成功' . PHP_EOL;
}


$actionFilePath = $rootPath . '/app/Action/Test';
if (!is_dir($actionFilePath)) {
    mkdir($actionFilePath, 0755, true);
}
$actionCOde = <<<COde
<?php
// Test控制器的Hello
namespace App\Action\Test;

use BaAGee\NkNkn\Base\ActionAbstract;

class Hello extends ActionAbstract
{
    protected function execute(array \$params = [])
    {
        return 'hello world';
    }
}

COde;
$actionFile = $actionFilePath . '/Hello.php';
if (is_file($actionFile)) {
    echo 'app/Action/Test/Hello.php 已经存在，已忽略' . PHP_EOL;
} else {
    file_put_contents($actionFile, $actionCOde);
    echo 'app/Action/Test/Hello.php 创建成功' . PHP_EOL;
}

echo '尝试访问一下 /api/hello' . PHP_EOL;

echo 'OVER' . PHP_EOL;
