<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/10/22
 * Time: 21:21
 */

namespace BaAGee\NkNkn\Base\TraitFunc;

use BaAGee\NkNkn\Constant\CoreNoticeCode;
use BaAGee\NkNkn\UserNotice;
use BaAGee\ParamsValidator\Base\ParamInvalid;
use BaAGee\ParamsValidator\Validator;

/**
 * Trait ParamsValidatorTrait
 * @package BaAGee\NkNkn\Base\TraitFunc
 */
trait ParamsValidatorTrait
{
    /**
     * 批量参数验证
     * @param array $params
     * @param array $rules
     * @return array
     * @throws \Exception
     */
    protected static function batchCheckParams(array $params, array $rules)
    {
        try {
            return Validator::getInstance()->batchAddRules($params, $rules)->validate();
        } catch (ParamInvalid $p) {
            //ParamInvalid 转化为用户提示
            throw new UserNotice($p->getMessage(), CoreNoticeCode::PARAMS_INVALID);
        }
    }
}
