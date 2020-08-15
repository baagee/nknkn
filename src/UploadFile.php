<?php
/**
 * Desc: 文件上传类
 * User: baagee
 * Date: 2018/10/20
 * Time: 下午8:39
 */

namespace BaAGee\NkNkn;

use BaAGee\NkNkn\Constant\CoreNoticeCode;

/**
 * Class UploadFile
 * @package App\Library
 */
class UploadFile
{
    /**
     * @var array
     */
    private $config = [
        'maxSize' => -1,    // 上传文件的最大值 单位byte
        'allowExts' => [],    // 允许上传的文件后缀 留空不作后缀检查
        'allowTypes' => [],    // 允许上传的文件类型 留空不做检查
        'savePath' => '',// 上传文件保存路径
        'autoSub' => false,// 启用子目录保存文件
        'subType' => 'hash',// 子目录创建方式 可以使用hash date
        'dateFormat' => 'Ymd',
        'autoCheck' => true, // 是否自动检查是否合法
        'uploadReplace' => false,// 存在同名是否覆盖
        'saveRule' => 'uniqid',// 上传文件命名规则函数名
        'hashType' => 'md5_file',// 上传文件Hash规则函数名
    ];

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * UploadFile constructor.
     * @param array $config 配置数组
     */
    public function __construct(array $config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 上传保存一个文件
     * @param $file
     * @return bool
     */
    private function save($file)
    {
        $filename = $file['save_path'] . $file['save_name'];
        if (!$this->uploadReplace && is_file($filename)) {
            // 不覆盖同名文件
            throw new UserNotice('上传的文件已存在', CoreNoticeCode::UPLOAD_FILE_EXISTS);
        }
        // 如果是图像文件 检测文件格式
        if (in_array(strtolower($file['extension']), ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
            $info = getimagesize($file['tmp_name']);
            if (false === $info || ('gif' == strtolower($file['extension']) && empty($info['bits']))) {
                throw new UserNotice('不是合法的图片文件', CoreNoticeCode::ILLEGAL_IMAGE_FILE);
            }
        }
        if (!move_uploaded_file($file['tmp_name'], $this->autoCharset($filename, 'utf-8', 'gbk'))) {
            throw new UserNotice('文件上传保存失败', CoreNoticeCode::UPLOAD_FILE_FAILED);
        }
        return true;
    }

    /**
     * 上传所有文件
     * @param string $savePath 保存文件的路径
     * @return array
     * @throws \Exception
     */
    public function upload($savePath = '')
    {
        //如果不指定保存文件名，则由系统默认
        if (empty($savePath)) {
            $savePath = $this->savePath;
        }
        // 检查上传目录
        if (!is_dir($savePath) || !is_writeable($savePath)) {
            if (!@mkdir($savePath, 0777, true)) {
                if (!is_dir($savePath)) {
                    throw new \Exception(sprintf('创建文件夹失败:%s', $savePath));
                }
            }
        }
        $fileInfos = [];
        $isUpload = false;

        // 获取上传的文件信息
        // 对$_FILES数组信息处理
        $files = $this->dealFiles($_FILES);
        foreach ($files as $key => $file) {
            //过滤无效的上传
            if (!empty($file['name'])) {
                //登记上传文件的扩展信息
                if (!isset($file['field'])) {
                    $file['field'] = $key;
                }
                $file['extension'] = $this->getExt($file['name']);
                $file['save_path'] = rtrim($savePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $file['save_name'] = $this->getSaveName($file);

                // 自动检查附件
                if ($this->autoCheck) {
                    $this->check($file);
                }
                //保存上传文件
                $this->save($file);
                if (function_exists($this->hashType)) {
                    $fun = $this->hashType;
                    $file['file_hash'] = $fun($this->autoCharset($file['save_path'] . $file['save_name'], 'utf-8', 'gbk'));
                }
                //上传成功后保存文件信息，供其他地方调用
                $field = $file['field'];
                unset($file['tmp_name'], $file['error'], $file['field']);
                $fileInfos[$field][] = $file;
                $isUpload = true;
            }
        }
        if ($isUpload) {
            return $fileInfos;
        } else {
            throw new UserNotice('文件上传失败', CoreNoticeCode::UPLOAD_FILE_FAILED);
        }
    }

    /**
     * 转换上传文件数组变量为正确的方式
     * @param $files
     * @return array
     */
    private function dealFiles($files)
    {
        $fileArray = [];
        $n = 0;
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $keys = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    $fileArray[$n]['field'] = $key;
                    foreach ($keys as $_key) {
                        $fileArray[$n][$_key] = $file[$_key][$i];
                    }
                    $n++;
                }
            } else {
                $fileArray[$key] = $file;
            }
        }
        return $fileArray;
    }

    /**
     * @param $errorNo
     * @throws \Exception
     */
    protected function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
                throw new \Exception('上传文件大小超过服务器限制');
            case 2:
                throw new \Exception('上传文件的大小超过了HTML表单中MAX_FILE_SIZE选项指定的值');
            case 3:
                throw new \Exception('仅上传文件的一部分');
            case 4:
                throw new \Exception('没有文件上传');
            case 6:
                throw new \Exception('找不到临时文件夹');
            case 7:
                throw new \Exception('文件写入失败');
            default:
                throw new \Exception('上传失败');
        }
    }

    /**
     * 根据上传文件命名规则取得保存文件名
     * @param $filename
     * @return string
     */
    private function getSaveName($filename)
    {
        $rule = $this->saveRule;
        if (empty($rule)) {//没有定义命名规则，则保持文件名不变
            $saveName = $filename['name'];
        } else {
            $ext = empty($filename['extension']) ? '' : '.' . $filename['extension'];
            if (function_exists($rule)) {
                //使用函数生成一个唯一文件标识号
                $saveName = $rule() . $ext;
            } else {
                //使用给定的文件名作为标识号
                $saveName = $rule . $ext;
            }
        }
        if ($this->autoSub) {
            // 使用子目录保存文件
            $filename['save_name'] = $saveName;
            $saveName = $this->getSubName($filename) . $saveName;
        }
        return $saveName;
    }

    /**
     * 获取子目录的名称
     * @param $file
     * @return string
     */
    private function getSubName($file)
    {
        switch ($this->subType) {
            case 'date':
                $dir = date($this->dateFormat, time()) . DIRECTORY_SEPARATOR;
                break;
            case 'hash':
            default:
                $name = md5($file['save_name']);
                $dir = $name . DIRECTORY_SEPARATOR;
                break;
        }
        if (!is_dir($file['save_path'] . $dir)) {
            mkdir($file['save_path'] . $dir, 0777, true);
        }
        return $dir;
    }

    /**
     * 检查上传的文件
     * @param $file
     * @return bool
     * @throws \Exception
     */
    private function check($file)
    {
        if ($file['error'] !== 0) {
            //文件上传失败, 捕获错误代码
            $this->error($file['error']);
        }
        //文件上传成功，进行自定义规则检查
        //检查文件大小
        if (!$this->checkSize($file['size'])) {
            throw new UserNotice('文件大小超过'
                . number_format(($this->maxSize / (1024 * 1024)), 2, '.', '') . 'MB',
                CoreNoticeCode::FILE_SIZE_TOO_LARGE);
        }

        //检查文件Mime类型
        if (!$this->checkType($file['type'])) {
            throw new UserNotice('上传文件MIME类型不在此范围中[' . implode(",", $this->allowTypes) . ']',
                CoreNoticeCode::UPLOAD_FILE_MIME_TYPE_INVALID);
        }
        //检查文件类型
        if (!$this->checkExt($file['extension'])) {
            throw new UserNotice('上传文件扩展名不在此范围中[' . implode(",", $this->allowExts) . ']',
                CoreNoticeCode::UPLOAD_FILE_EXT_TYPE_INVALID);
        }

        //检查是否合法上传
        if (!$this->checkUpload($file['tmp_name'])) {
            throw new UserNotice('上传文件不合法', CoreNoticeCode::ILLEGAL_UPLOAD_FILE);
        }
        return true;
    }

    // 自动转换字符集 支持数组转换

    /**
     * @param        $fContents
     * @param string $from
     * @param string $to
     * @return string
     */
    private function autoCharset($fContents, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    }

    /**
     * 检查上传的文件类型是否合法
     * @param $type
     * @return bool
     */
    private function checkType($type)
    {
        if (!empty($this->allowTypes))
            return in_array(strtolower($type), $this->allowTypes);
        return true;
    }


    /**
     * 检查上传的文件后缀是否合法
     * @param $ext
     * @return bool
     */
    private function checkExt($ext)
    {
        if (!empty($this->allowExts)) {
            return in_array(strtolower($ext), $this->allowExts, true);
        } else {
            return true;
        }
    }

    /**
     * 检查文件大小是否合法
     * @param $size
     * @return bool
     */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (-1 == $this->maxSize);
    }

    /**
     * 检查文件是否非法提交
     * @param $filename
     * @return bool
     */
    private function checkUpload($filename)
    {
        return is_uploaded_file($filename);
    }

    /**
     * 取得上传文件的后缀
     * @param $filename
     * @return mixed
     */
    private function getExt($filename)
    {
        $pathinfo = pathinfo($filename);
        return $pathinfo['extension'] ?? '';
    }
}