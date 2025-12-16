<?php

namespace Imee\Comp\Nocode\Service\Exception;

class FormException extends BaseException
{
    protected $serviceCode = '13';

    const NCID_NOT_FOUND = ['01', '标识不能为空'];   // 10111301
    const DELETE_ERROR = ['02', '删除失败，失败原因：%s'];   // 10111302
    const MODULE_ID_NOT_FOUND = ['03', '模块ID不能为空'];   // 10111303
    const SCHEMA_JSON_NOT_FOUND = ['04', '表单Schena Json不能为空'];   // 10111304
    const SCHEMA_JSON_FORMAT_ERROR = ['05', '表单Schena Json格式错误'];   // 10111305
    const NCID_NOT_EXISTS = ['06', '标识不存在'];   // 10111306
    const SAVE_ERROR = ['07', '保存失败，失败原因：%s'];   // 10111307
    const CONTROLLER_NOT_FOUND = ['08', '控制器不能为空'];   // 10111308
    const MODULE_NAME_NOT_FOUND = ['09', '模块名称不能为空'];   // 10111309
    const NCID_EXSITS_ERROR = ['10', '标识已存在'];   // 10111310
    const SCHEMA_JSON_DECODE_ERROR = ['11', '表单Schena Json解析错误'];   // 10111311
    const NCID_INVALID = ['12', '标识只能包含小写字母'];   // 10111312
}
