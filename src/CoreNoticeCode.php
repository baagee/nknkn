<?php
/**
 * Desc: 核心错误码
 * User: baagee
 * Date: 2019/9/23
 * Time: 12:02
 */

namespace BaAGee\NkNkn;

class CoreNoticeCode
{
    const DEFAULT_ERROR_CODE = 1000;

    const PARAMS_INVALID = 1001;

    const UPLOAD_FILE_EXISTS = 1002;

    const ILLEGAL_IMAGE_FILE = 1003;

    const UPLOAD_FILE_FAILED = 1004;

    const FILE_SIZE_TOO_LARGE = 1005;

    const UPLOAD_FILE_MIME_TYPE_INVALID = 1006;

    const UPLOAD_FILE_EXT_TYPE_INVALID = 1007;

    const ILLEGAL_UPLOAD_FILE = 1008;

    const CURL_REQUEST_FAILED = 1009;
}
