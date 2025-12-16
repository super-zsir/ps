<?php

namespace Imee\Exception\Cs;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{
    protected $moduleCode = self::MODULE_CS;
    protected $serviceCode = '00';
}
