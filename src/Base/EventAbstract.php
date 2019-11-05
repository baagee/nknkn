<?php

namespace BaAGee\NkNkn\Base;

use BaAGee\Log\Log;

abstract class EventAbstract
{
    abstract protected function run($args = null);

    final public function main()
    {
        $stime = microtime(true);
        Log::alert(sprintf('%s start!', static::class));
        $params = func_get_args();
        $this->run($params);
        $etime = microtime(true);
        $time  = number_format(($etime - $stime) * 1000, 3, '.', '');
        Log::alert(sprintf('%s end! time:' . $time . 'ms', static::class));
    }
}
