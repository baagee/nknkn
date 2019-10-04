<?php
/**
 * Desc: Curl请求类
 * User: baagee
 * Date: 2019/10/4
 * Time: 22:15
 */

namespace BaAGee\NkNkn\Base;

use BaAGee\Config\Config;
use BaAGee\CurlRequest\MultipleRequest;
use BaAGee\CurlRequest\SingleRequest;
use BaAGee\Log\Log;
use BaAGee\NkNkn\CoreNoticeCode;

/**
 * Class CurlRequest
 * @package BaAGee\NkNkn\Base
 */
abstract class CurlRequest extends SingleRequest
{
    /**
     * @var string
     */
    protected $serviceName = '';

    /**
     * CurlRequest constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getConfig());
    }

    /**
     * 单个请求
     * @param string $path
     * @param        $params
     * @param string $method
     * @param array  $headers
     * @param string $cookies
     * @return string
     * @throws \Exception
     */
    public function request(string $path, $params, string $method, array $headers = [], string $cookies = '')
    {
        Log::info(sprintf('CurlRequest start serviceName:%s path:%s params:%s method:%s headers:%s cookies:%s',
            $this->serviceName, $path, is_array($params) ? json_encode($params) : $params, $method,
            json_encode($headers), $cookies));
        $res = parent::request($path, $params, $method, $headers, $cookies);
        Log::info(sprintf('CurlRequest end serviceName:%s path:%s all result:%s', $this->serviceName,
            $path, json_encode($res)));

        if ($res['errno'] == 0) {
            return $res['result'];
        } else {
            throw new \Exception($res['errmsg'], CoreNoticeCode::CURL_REQUEST_FAILED);
        }
    }

    /**
     * 批量请求
     * @param $params
     * @return array|null
     */
    public function multipleRequest($params)
    {
        $req = new MultipleRequest($this->getConfig());
        Log::info(sprintf('MultipleRequest start serviceName:%s allParams:%s', $this->serviceName, json_encode($params)));
        $res = $req->request($params);
        Log::info(sprintf('MultipleRequest end serviceName:%s all result:%s', $this->serviceName, json_encode($res)));
        return $res;
    }

    /**
     * @return array|mixed|null
     */
    private function getConfig()
    {
        $config = Config::get('http/' . $this->serviceName);
        if (empty($config)) {
            $config = [];
        }
        return $config;
    }
}
