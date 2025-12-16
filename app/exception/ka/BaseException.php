<?php

namespace Imee\Exception\Ka;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{
    protected $moduleCode = self::MODULE_KA;
    protected $serviceCode = '00';
}
