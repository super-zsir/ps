<?php

namespace Imee\Exception\Audit;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{


    protected $moduleCode = self::MODULE_AUDIT;
    protected $serviceCode = '00';

    const RISKCHECK = 01;
    const SENSITIVE = 02;//敏感词
}
