<?php


namespace Imee\Service\Domain\Service\Audit\Exception;


use Imee\Exception\ReportException;

class CsmsException extends ReportException
{

    protected $moduleCode = self::MODULE_AUDIT;
    protected $serviceCode = '00';


    const CIRCLE_REPORT_EMPTY = ['10', '清空原因不可为空'];
    const CIRCLE_REPORT_ERROR = ['11', '操作有误'];
}