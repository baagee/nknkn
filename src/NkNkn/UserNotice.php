<?php
/**
 * Desc: 能让用户知道的错误异常
 * User: baagee
 * Date: 2019/9/23
 * Time: 11:12
 */

namespace BaAGee\NkNkn;

/**
 * Class UserNotice
 * @package BaAGee\NkNkn
 */
class UserNotice extends \LogicException
{
    /**
     * @var array
     */
    protected $errorData = '';

    /**
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * UserNotice constructor.
     * @param       $message
     * @param       $code
     * @param       $errorData
     */
    public function __construct(string $message, $code, $errorData = '')
    {
        if (empty($message)) {
            $message = '未知错误~';
        }
        if (empty($code)) {
            $code = CoreNoticeCode::DEFAULT_ERROR_CODE;
        }
        parent::__construct($message, $code, null);
        $this->errorData = $errorData;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }
}
