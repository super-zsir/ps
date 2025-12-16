<?php

namespace Imee\Exception;

/**
 * 这个为特殊注释类，在此加入的注释为各个系统的公共类，不满足8位码
 */
class CommonException extends ReportException
{
    const NO_PERMISS_ERROR = ['403', "你没有权限进行此项操作%s"];
    const NO_LOGIN_ERROR = ['401', "token过期"];
    const NO_FOUND_ERROR = ['404', "NO FOUND"];
    const VALIDATION_ERROR = ['-1001', "验证错误"];

    public function getOutCode()
    {
        return $this->getCode();
    }
}
