<?php


namespace Imee\Exception\Audit;

class AuditWorkbenchException extends BaseException
{

    // 06 审核工作台
    protected $serviceCode = '06';


    const PARAMS_ERROR = ['05', '参数错误'];


    const MODULE_NOT_EXIST = ['10', '该审核模块尚未接入'];
    const HANDLE_NOT_EXIST = ['11', '请传入操作方法handle'];

    // 审核工作台
    const TASKLIST_PARAM_ERROR = ['20', '请传入模块和选项'];
    const STAFF_MODULE_POWER_NOTEXIST = ['21', '暂无该模块审核权限，请联系管理员'];
    const STAFF_MODULE_CHOICE_NOTEXIST = ['22', '该模块下暂未分配选项权限，请联系管理员'];
    const TASKLIST_TIME_ERROR = ['23', '请选择开始时间和结束时间'];
    const STAFF_MODULE_CHOICE_NONE = ['24', '暂无该模块审核项权限，请联系管理员'];
    const STAFF_HAS_TASK = ['25', '您还有未处理的任务，请先处理后再获取'];
    const REDIS_TASK_LOCK = ['26', '其他人正在获取任务，请稍后获取'];
    const STAFF_HAS_NOT_APP = ['27', '暂无APP权限，请联系管理员'];
    const NOT_HAS_NEW_TASK = ['28', '暂无未处理问题，可以休息一下啦'];


    const MULT_NOT_PASS = ['30', '暂不支持批量拒绝'];
    const TASK_TIME_OUT = ['31', '该审核任务已超时，请刷新数据或重新获取任务'];
    const TASK_VERIFY_ERROR = ['32', '错误或者重复的请求'];
    const REASON_NOT_NULL = ['33', '清空原因不可为空'];
    const DATA_NOT_EXIST = ['34', '当前数据不存在'];
    const MULT_LOGIC_ERROR = ['35', '此逻辑不符合要求'];



    const MULT_PASS_ERROR = ['50', '操作有误'];

    const SPECIAL_ERROR = ['98', '该模块暂无此方法，请联系管理员'];
    const SYSTEM_ERROR = ['99', '审核工作台系统错误，请联系管理员'];

	// 大神技能
    const USER_GOD_USERINFO = ['60', '用户资料目前是未审核的状态，不能将此用户的技能设置为已通过'];

    // 大神视频
	const GOD_VIDEO_UNPASS = ['65', '该视频还未审核通过'];
	const GOD_VIDEO_NOTVERIFY = ['66', '没有已经认证的视频了，无法取消认证'];
	const GOD_VIDEO_CANNOTSTATE = ['68', '当前不能修改，请确认'];
	const GOD_VIDEO_STATEERROR = ['69', '不能操作推荐视频和认证视频'];

	// C位
    const GRABMIC_SONG_SCORE_ERROR = ['70', '打分标准为0-100的整数值'];
}
