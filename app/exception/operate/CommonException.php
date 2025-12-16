<?php

namespace Imee\Exception\Operate;

class CommonException extends BaseException
{
    protected $serviceCode = '07';

    const EXPORT_FILE_NO_DATA = ['00', '没有文件'];
    const EXPORT_FILE_EXT_INCORRECT = ['01', '上传格式不正确'];
    const EXPORT_FILE_NO_EXIST = ['02', '没有文件'];
    const EXPORT_FILE_OPEN_FAIL = ['03', '打开文件失败'];
    const EXPORT_FILE_COLUMNS_NUM_WRONG = ['04', '文件列数不为%s，请查看是否有空列'];
    const FILTER_NO_SELECT = ['05', '请选择 %s'];
    const FILTER_NO_TEXT = ['06', '请填写 %s'];
    const NETWORK_ERROR = ['07', '网络异常,请重试'];
    const COMMON_NO_DATA = ['08', '数据不存在'];
    const COMMON_EXIST_DATA = ['09', '数据已存在'];
    const FILTER_NO_PARAM_MSG = ['10', '缺少参数 %s'];
    const COMMON_DATA_ERROR_REFERER = ['11', '数据错误，请刷新重试'];
    const COMMON_APP_NO_DATA = ['12', 'APP 不存在'];
    const COMMON_ENUM_NO_DATA = ['13', '%s 不存在此选项'];
    const COMMON_FIELD_LENGTH_MAX = ['14', '%s 长度不可以超过 %s'];
    const COMMON_FIELD_TIME_NOT_ELT_NOW = ['15', '%s 不能小于当前时间'];
    const COMMON_FIELD_COUNT_MAX = ['14', '%s 个数不可以超过 %s'];
    const COMMON_GET_INFO_FAIL = ['15', '获取详情数据失败'];
    const COMMON_ILLEGAL  = ['16', '非法操作'];
    const COMMON_FIELD_NUMBER_MIN = ['17', '%s 的最小值 %s'];
    const COMMON_FIELD_NUMBER_MAX = ['18', '%s 的最大值 %s'];
    const COMMON_FIELD_MUST_NUMBER = ['19', '%s 必须是一个数字'];


}
