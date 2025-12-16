<?php

namespace Imee\Service\Domain\Service\Csms\Exception\Saas;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{

    protected $moduleCode = '08';
    protected $serviceCode = '00';
}
