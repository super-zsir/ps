<?php
namespace Imee\Exception\Audit;

class SensitiveException extends BaseException
{
    protected $serviceCode = self::SENSITIVE;

    const REMOVE_DATA_FAILED = ['00', '删除失败'];
    const EMPTY_DATA_ERROR = ['01', '新增的数据为空'];
    const CREATE_DATA_FAILED = ['02', '新增失败'];
    const MODIFY_DATA_FAILED = ['03', '修改失败'];
}
