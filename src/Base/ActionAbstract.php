<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/10/22
 * Time: 20:50
 */

namespace BaAGee\NkNkn\Base;

/**
 * Class ActionAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class ActionAbstract
{
    use ParamsValidatorTrait;

    /**
     *  参数验证规则
     * @var array
     */
    protected $paramRules = [];

    /**
     * @param array $params
     * @return mixed
     */
    abstract protected function execute(array $params = []);

    /**
     * action入口函数
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function main(array $params = [])
    {
        if (!empty($this->paramRules)) {
            $params = $this->batchCheckParams($params, $this->paramRules);
        }
        return $this->execute($params);
    }
}
