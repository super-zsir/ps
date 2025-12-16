<?php


namespace Imee\Helper\Constant;

/**
 * 内容安全管理常量
 * Class CsmsConstant
 * @package Imee\Helper\Constant
 */
class CsmsConstant
{

	//----------------------------csms------------------------------


	// 接入方式
	const CSMS_NSQ = 'nsq';
	const CSMS_RPC = 'rpc';
	const CSMS_KAFKA = 'kafka';
	const CSMS_API = 'api';


	// 内容安全标准审核模块名
	const CSMS_AUDIT = 'csmsaudit';                 // 初审
	const CSMS_RECHECK = 'recheckcsms';             // 复审
	const CSMS_INSPECT = 'inspectcsms';             // 质检

	public static $csms_stages = [
		self::CSMS_AUDIT => '初审',
		self::CSMS_RECHECK => '复审',
		self::CSMS_INSPECT => '质检',
	];


	// 内容安全管理预警通知地址
	const CSMS_WECHAT_URL = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=2bc38e93-7246-481c-b050-49db23754126';

	// 内容安全异常msg 标识
	const CSMS_MSG = 'csms_msg';

	// 状态 1 正常
	const STATE_NORMAL = 1;
	// 状态 2 下线
	const STATE_OFFLINE = 2;


	/**
	 * 审核状态
	 */
	const CSMS_STATE_DEFAULT = 0;
	const CSMS_STATE_PASS = 1;
	const CSMS_STATE_REJECT = 2;
	const CSMS_STATE_UNCHECK = 3;
	const CSMS_STATE_DELETE = 4;
	const CSMS_STATE_RECALL = 5;

	public static $csms_state = [
		self::CSMS_STATE_DEFAULT => '默认',
		self::CSMS_STATE_PASS => '通过',
		self::CSMS_STATE_REJECT => '拒绝',
		self::CSMS_STATE_UNCHECK => '待审',
		self::CSMS_STATE_DELETE => '已删除',
		self::CSMS_STATE_RECALL => '已撤回',
	];



	// 审核三阶段
	const STAGE_OP = 'op';
	const STAGE_OP2 = 'op2';
	const STAGE_OP3 = 'op3';
	public static $csms_stage = [
		self::STAGE_OP => '初审',
		self::STAGE_OP2 => '复审',
		self::STAGE_OP3 => '质检',
	];


	// 只允许审核人员操作的状态
    public static $allow_state = [
        self::CSMS_STATE_PASS,
        self::CSMS_STATE_REJECT,
    ];

    // 不可更改的状态
    const UNCHANGE_STATE = [
        self::CSMS_STATE_DELETE,
        self::CSMS_STATE_REJECT,
    ];

    // 类型
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';

    public static $csms_type = [
    	self::TYPE_TEXT => '文本',
    	self::TYPE_IMAGE => '图片',
    	self::TYPE_AUDIO => '音频',
    	self::TYPE_VIDEO => '视频',
    ];

	const TASK_DEFAULT_NUMBER = 100;

	// =====================redis=========================
	const REDIS_STAFF_TASK_PRE = 'newkefu:staff_task:';
	const REDIS_MODULE_USER = 'newkefu:module_user:';
	const REDIS_TASK_TTL = 600;
	const REDIS_TASK_LOCK_PRE = 'newkefu:new_task_lock:';
	const REDIS_TASK_LOCK_TTL = 5;




	// 模型
	const SYSTEM_OP = 9999;
	const SYSTEM_OP_NAME = '模型';


    const CSMS_REVIEW_NO = 0;
    const CSMS_REVIEW_YES = 1;

    public static $csms_review = [
        self::CSMS_REVIEW_NO => '先发后审',
        self::CSMS_REVIEW_YES => '先审后发'
    ];



    // 内容安全-任务变更常量
    const CSMS_CHANGE_TYPE_DELETED = 'deleted';
    const CSMS_CHANGE_TYPE_CALLBACK = 'callback';
    const CSMS_CHANGE_TYPE_URGE = 'urge';

    public static $csms_change_type = [
        self::CSMS_CHANGE_TYPE_DELETED => '删除',
        self::CSMS_CHANGE_TYPE_CALLBACK => '撤回',
        self::CSMS_CHANGE_TYPE_URGE => '催审核'
    ];

    // 内容安全-任务变更来源
    const CSMS_CHANGE_SOURCE_CSMS = 'csms';
    const CSMS_CHANGE_SOURCE_SUPER = 'super';
    const CSMS_CHANGE_SOURCE_SYSTEM = 'system';
    const CSMS_CHANGE_SOURCE_CS = 'cs';

    public static $csms_change_source = [
        self::CSMS_CHANGE_SOURCE_CSMS => 'CSMS',
        self::CSMS_CHANGE_SOURCE_SUPER => '超管',
        self::CSMS_CHANGE_SOURCE_SYSTEM => '服务端',
        self::CSMS_CHANGE_SOURCE_CS => '客服'
    ];


}