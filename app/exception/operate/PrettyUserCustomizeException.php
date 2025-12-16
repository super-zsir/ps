<?php

namespace Imee\Exception\Operate;

class PrettyUserCustomizeException extends BaseException
{
    protected $serviceCode = parent::SERVICE_CODE_PRETTY_USER_CUSTOMIZE;

    const UIDS_RULE_ERROR = ['00', 'uid所传格式有误，请检查'];

    const UID_NOEXIST_ERROR = ['01', '所传UID中有无效用户，请检查'];
    const TYPE_NOEXIST_ERROR = ['02', '类型不存在，请检查'];
    const IMPORT_FAIL_ERROR = ['03', '第%s行导入失败，原因:%s'];
    const CURL_FAIL_ERROR = ['04', '接口访问失败，原因:%s'];
    const DATA_NOEXIST_ERROR = ['05', '数据不存在'];
    const BIG_AREA_ERROR = ['06', '你没有发放自选靓号给%s的权限'];
    const IMPORT_NUM_ERROR = ['07', '导入数量最大支持%d条'];
}
