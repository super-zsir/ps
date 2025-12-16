<?php
namespace Imee\Exception\Cs;

class ChannelException extends BaseException
{
    protected $serviceCode = '01';
    const USER_NOT_FOUND = ['00', '用户不存在'];
    const CREATE_FAIL_ERROR = ['01', '创建失败'];
}
