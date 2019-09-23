<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-08-25
 * Time: 01:01
 */

namespace BaAGee\NkNkn\Base;

use BaAGee\ParamsValidator\Base\ParamInvalid;
use BaAGee\ParamsValidator\Validator;
use BaAGee\NkNkn\CoreNoticeCode;
use BaAGee\NkNkn\UserNotice;

/**
 * Class ControllerAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class ControllerAbstract
{
    /**
     * @param array $params
     * @param array $rules
     * @return array
     * @throws \Exception
     */
    public function batchCheckParams(array $params, array $rules)
    {
        try {
            return Validator::getInstance()->batchAddRules($params, $rules)->validate();
        } catch (ParamInvalid $p) {
            //ParamInvalid 转化为用户提示
            throw new UserNotice($p->getMessage(), CoreNoticeCode::PARAMS_INVALID);
        }
    }
}
