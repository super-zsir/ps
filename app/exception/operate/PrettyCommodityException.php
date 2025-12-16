<?php

namespace Imee\Exception\Operate;

class PrettyCommodityException extends BaseException
{
    protected $serviceCode = parent::SERVICE_CODE_PRETTY_COMMODITY;

    const DATA_NOEXIST_ERROR = ['00', '数据不存在'];

    const PRETTYUSER_REPEAT_ERROR = ['01', '靓号ID重复'];
    const DATA_DIFF_ERROR = ['02', '数据不一致'];
}
