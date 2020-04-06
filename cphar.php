<?php
/**
 * Desc: 生成工具
 * User: baagee
 * Date: 2018/8/15
 * Time: 下午4:25
 */
date_default_timezone_set('PRC');


define('DS', DIRECTORY_SEPARATOR);
$phar = new Phar('creater.phar', 0, 'creater');
$phar->buildFromDirectory(__DIR__ . '/creater');
$phar->addFromString(DS . 'build_time', date('Y-m-d H:i:s'));
$phar->compressFiles(Phar::GZ);
$phar->setDefaultStub('creater.php');

$bin_path = __DIR__ . DS . 'bin';
$bin_file = $bin_path . DS . 'creater';
if (!is_dir($bin_path)) {
    mkdir($bin_path);
} else {
    if (is_file($bin_file)) {
        unlink($bin_file);
    }
}

copy(__DIR__ . '/creater.phar', $bin_file);

unlink(__DIR__ . '/creater.phar');

echo 'over' . PHP_EOL;