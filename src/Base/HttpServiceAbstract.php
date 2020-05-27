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
use BaAGee\NkNkn\Constant\CoreNoticeCode;
use \BaAGee\NkNkn\Base\TraitFunc\TimerTrait;
/**
 * Class HttpServiceAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class HttpServiceAbstract extends SingleRequest
{
    use TimerTrait;
    /**
     * @var string
     */
    protected $serviceName = '';

    /**
     * HttpServiceAbstract constructor.
     * @throws \Exception
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
     * @return string
     * @throws \Exception
     */
    public function request(string $path, $params, string $method)
    {
        Log::info(sprintf('CurlRequest start serviceName:%s path:%s params:%s method:%s headers:%s cookies:%s',
            $this->serviceName, $path, is_array($params) ? json_encode($params) : $params, $method,
            json_encode($this->headers), $this->cookies));
        $this->headers[] = 'x-trace-id: ' . \BaAGee\NkNkn\AppEnv::get('TRACE_ID');
        list($res, $time) = self::executeTime(function ($path, $params, $method) {
            return parent::request($path, $params, $method);
        }, 0, $path, $params, $method);
        Log::info(sprintf('CurlRequest end time: %sms serviceName:%s path:%s all result:%s', $time, $this->serviceName,
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
     * @throws \Exception
     */
    public function multipleRequest($params)
    {
        foreach ($params as &$item) {
            $item['headers'][] = 'x-trace-id: ' . \BaAGee\NkNkn\AppEnv::get('TRACE_ID');
        }
        unset($item);
        $req = new MultipleRequest($this->getConfig());
        Log::info(sprintf('MultipleRequest start serviceName:%s allParams:%s', $this->serviceName, json_encode($params)));
        list($res, $time) = self::executeTime([$req, 'request'], 0, $params);
        Log::info(sprintf('MultipleRequest end time: %sms serviceName:%s all result:%s', $time, $this->serviceName, json_encode($res)));
        return $res;
    }

    /**
     * @return array|mixed|null
     * @throws \Exception
     */
    private function getConfig()
    {
        if (empty($this->serviceName)) {
            throw new \Exception("serviceName不能为空", CoreNoticeCode::SERVICE_NAME_EMPTY);
        }
        $config = Config::get('service/' . $this->serviceName, []);
        if (empty($config)) {
            $config = [];
        }
        return $config;
    }
}
