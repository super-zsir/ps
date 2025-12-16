<?php

namespace Imee\Exception\Cs;

class WorkbenchException extends BaseException
{
    protected $serviceCode = '05';
    const USER_NOT_FOUND = ['00', '用户不存在'];
}
