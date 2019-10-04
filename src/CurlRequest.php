<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/10/4
 * Time: 22:15
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\CurlRequest\SingleRequest;

abstract class CurlRequest extends SingleRequest
{
    protected $serviceName = '';

    public function __construct()
    {
        $config = Config::get('http/' . $this->serviceName);
        if (empty($config)) {
            $config = [];
        }
        parent::__construct($config);
    }

    public function request(string $path, $params, string $method, array $headers = [], string $cookies = '')
    {
        $res = parent::request($path, $params, $method, $headers, $cookies);
        var_dump($res);
        return $res;
    }
}
