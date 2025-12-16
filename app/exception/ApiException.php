<?php

namespace Imee\Exception;

class ApiException extends \Exception
{
    //在此扩充错误码
    const SUCCESS = 0;

    const MSG_ERROR = 100;
    const VALIDATION_ERROR = 101;
    const PARAMS_ERROR = 102;

    const NSQ_SEND_ERROR = 201;

    const TOKEN_INVALID_ERROR = 401;
    const NO_LOGIN_ERROR = 402;
    const NO_PERMISS_ERROR = 403;
    const NO_FOUND_ERROR = 404;
    const PATH_NOEXISTS_ERROR = 405;
    const ROUTE_NOEXISTS_ERROR = 406;
    const TYPE_NO_MATCH_ERROR = 407;
    const MODULE_NOEXIST_ERROR = 408;
    const PARENT_MODULE_NOEXIST_ERROR = 409;
    const MODULE_HAS_CHILDREN_ERROR = 410;
    const CREATE_FAIL_ERROR = 411;
    const MODIFY_FAIL_ERROR = 412;
    const EMAIL_EXIST_ERROR = 413;
    const ROLE_NOEXIST_ERROR = 414;
    const DATA_NOEXIST_ERROR = 415;
    const ACCOUNT_ERROR = 416;
    const REPASSWORD_ERROR = 417;
    const FORBIDDEN_ERROR = 418;
    const SYSTEM_PERMISSION_ERROR = 419;
    const LOGIN_PLEASE_AGAIN_ERROR = 420;
    const LOGIN_GET_INFO_ERROR = 421;
    const LOGIN_SAVE_ERROR = 422;
    const LOGIN_TOURIST_NO_LOGIN_ERROR = 423;
    const NAME_REPEAT_ERROR = 424;
    const MODULE_PARENT_NOEXIST_ERROR = 425;

    const ACTION_NOEXIST_ERROR = 426;
    const NO_UPLOAD_ERROR = 427;
    const SOURCE_UNIDENTIFIED_ERROR = 428;
    const MIME_NOALLOW_ERROR = 429;
    const EXTENSION_NOALLOW_ERROR = 430;
    const UPLOAD_ERROR = 431;
    const VIDEO_SCREENSHOT_ERROR = 432;
    const FILE_SIZE_LARGE_ERROR = 433;
    const GIFT_UPLOAD_PARAMS_ERROR = 434;

    const PATH_ALREADY_EXISTS = 435;
    const MODULE_ERROR = 436;
    const GUID_ERROR = 437;
    const ADMIN_ID_ERROR = 438;
    const ADMIN_EMAIL_ERROR = 439;
    const ROLE_ERROR = 440;
    const USER_MODULE_EXISTS = 441;
    const MOVE_SELF_ERROR = 442;
    const MOVE_ROOT_ERROR = 443;
    const MODULE_APPLIED = 444;
    const SUPER_APPLY_ERROR = 445;
    const MODULE_BIGAREA_STATE_ERROR = 446;
    const MODULE_BIGAREA_ERROR = 447;
    const APPLY_ERROR = 448;
    const AUDIT_ERROR = 449;
    const MODULE_BIGAREA_COMPETE_ERROR = 450;
    const MOVE_TO_CHILD_ERROR = 451;

    protected $codeMsgList = [
        self::GIFT_UPLOAD_PARAMS_ERROR     => '上传参数缺失',
        self::FILE_SIZE_LARGE_ERROR        => '上传文件超过了允许的大小:%s',
        self::VIDEO_SCREENSHOT_ERROR       => '视频截图生成失败',
        self::UPLOAD_ERROR                 => '上传失败:%s',
        self::EXTENSION_NOALLOW_ERROR      => '该文件格式不允许上传',
        self::MIME_NOALLOW_ERROR           => '该文件真实格式不允许上传:%s',
        self::SOURCE_UNIDENTIFIED_ERROR    => '资源来历不明',
        self::NO_UPLOAD_ERROR              => '请上传文件',
        self::ACTION_NOEXIST_ERROR         => 'action不存在',
        self::MODULE_PARENT_NOEXIST_ERROR  => '所传子模块的父模块未传:%s',
        self::NAME_REPEAT_ERROR            => '角色名称已存在',
        self::LOGIN_TOURIST_NO_LOGIN_ERROR => '游客不能登录，如有需要请联系管理员',
        self::LOGIN_SAVE_ERROR             => '保存登录用户信息失败请重试',
        self::LOGIN_GET_INFO_ERROR         => '获取登录用户信息失败请重试',
        self::LOGIN_PLEASE_AGAIN_ERROR     => '登录发生错误请重试',
        self::SYSTEM_PERMISSION_ERROR      => '没有对应系统权限',
        self::FORBIDDEN_ERROR              => '已经被禁止登录',
        self::REPASSWORD_ERROR             => '二次验证错误，请重试',
        self::ACCOUNT_ERROR                => '账号或者密码错误，请重试',
        self::DATA_NOEXIST_ERROR           => '数据不存在',
        self::ROLE_NOEXIST_ERROR           => '请检查所传角色',
        self::EMAIL_EXIST_ERROR            => '用户邮箱已存在',
        self::MODIFY_FAIL_ERROR            => '修改失败:%s',
        self::CREATE_FAIL_ERROR            => '创建失败:%s',
        self::MODULE_HAS_CHILDREN_ERROR    => '请先删除子项',
        self::PARENT_MODULE_NOEXIST_ERROR  => '父模块不存在',
        self::MODULE_NOEXIST_ERROR         => '模块不存在',
        self::TYPE_NO_MATCH_ERROR          => '所传type与对应的模块不匹配',
        self::ROUTE_NOEXISTS_ERROR         => '路由不存在:%s',
        self::PATH_NOEXISTS_ERROR          => 'path不存在:%s',
        self::NO_FOUND_ERROR               => 'NO FOUND',
        self::NO_LOGIN_ERROR               => '未登录',
        self::NO_PERMISS_ERROR             => '你没有权限进行此项操作:%s',
        self::TOKEN_INVALID_ERROR          => 'token过期',
        self::VALIDATION_ERROR             => '验证错误:%s',
        self::MSG_ERROR                    => '%s',
        self::PARAMS_ERROR                 => '参数错误:%s',
        self::NSQ_SEND_ERROR               => 'NSQ发送失败',
        self::SUCCESS                      => 'ok',

        self::PATH_ALREADY_EXISTS          => 'path已存在',
        self::MODULE_ERROR                 => '模块id传递不正确',
        self::GUID_ERROR                   => 'guid不正确',
        self::ADMIN_ID_ERROR               => '后台用户ID错误',
        self::ADMIN_EMAIL_ERROR            => '用户邮箱错误',
        self::ROLE_ERROR                   => '角色不正确',
        self::USER_MODULE_EXISTS           => '该用户对应模块已存在',
        self::MOVE_SELF_ERROR              => '转移对象不能是自己',
        self::MOVE_ROOT_ERROR              => '一级菜单不支持转移',
        self::MODULE_APPLIED               => '该权限点已申请，不能重复提交:%s',
        self::SUPER_APPLY_ERROR            => '你已是超管身份，无需申请',
        self::MODULE_BIGAREA_STATE_ERROR   => '该功能未开启大区权限申请',
        self::MODULE_BIGAREA_ERROR         => '请选择正确的大区类型',
        self::APPLY_ERROR                  => '申请失败，请稍后再试:%s',
        self::AUDIT_ERROR                  => '审核失败，请稍后再试:%s',
        self::MODULE_BIGAREA_COMPETE_ERROR => '大区权限不能重复申请',
        self::MOVE_TO_CHILD_ERROR          => '不能转移到子菜单下',
    ];

    private $data;
    private $params;

    public function __construct($code = self::SUCCESS, $params = null, $data = [])
    {
        $this->data = $data;
        $this->params = $params;
        $this->code = $code;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMsg(): string
    {
        $msg = $this->codeMsgList[$this->code] ?? $this->codeMsgList[self::MSG_ERROR];

        if ($this->params && is_array($this->params)) {
            return sprintf($msg, ...$this->params);
        }

        return sprintf($msg, $this->params);
    }

    public function getMsgBase()
    {
        return $this->params;
    }
}
