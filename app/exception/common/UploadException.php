<?php

namespace Imee\Exception\Common;

class UploadException extends BaseException
{
    protected $serviceCode = '00';

    const ACTION_NOEXIST_ERROR = ['00', 'action不存在'];
    const NO_UPLOAD_ERROR = ['01', '请上传文件'];
    const SOURCE_UNIDENTIFIED_ERROR = ['02', '资源来历不明'];
    const MIME_NOALLOW_ERROR = ['03', '该文件真实格式不允许上传'];
    const EXTENSION_NOALLOW_ERROR = ['04', '该文件格式不允许上传'];
    const UPLOAD_ERROR = ['05', '上传失败'];
    const VIDEO_SCREENSHOT_ERROR = ['06', '视频截图生成失败'];
    const FILE_SIZE_LARGE_ERROR = ['07', '上传文件超过了允许的大小'];
    const GIFT_UPLOAD_PARAMS_ERROR = ['08', '上传参数缺失'];
}
