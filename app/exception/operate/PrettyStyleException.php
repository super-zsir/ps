<?php

namespace Imee\Exception\Operate;

class PrettyStyleException extends BaseException
{
    protected $serviceCode = parent::SERVICE_CODE_PRETTY_STYLE;

    const DATA_NOEXIST_ERROR = ['00', '数据不存在'];

    const NAME_REPEAT_ERROR = ['01', '类型名称重复'];
    const LIMIT_ERROR = ['02', '最长字符必须>=最短字符'];
}
