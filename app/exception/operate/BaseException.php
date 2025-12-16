<?php

namespace Imee\Exception\Operate;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{
    protected $moduleCode = parent::MODULE_OPERATE;
    protected $serviceCode = '00';

    const SERVICE_CODE_PRETTY_STYLE = '02';
    const SERVICE_CODE_PRETTY_USER_CUSTOMIZE = '03';
    const SERVICE_CODE_PRETTY_USER = '04';
    const SERVICE_CODE_PRETTY_COMMODITY = '05';
}
