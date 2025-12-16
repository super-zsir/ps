<?php

namespace Imee\Exception\Common;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{
    protected $moduleCode = self::MODULE_COMMON;
    protected $serviceCode = '00';
}
