<?php

namespace Imee\Comp\Nocode\Service\Exception;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{
    protected $moduleCode = '12';
    protected $serviceCode = '00';
}
