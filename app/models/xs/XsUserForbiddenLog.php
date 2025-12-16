<?php

namespace Imee\Models\Xs;

class XsUserForbiddenLog extends BaseModel
{

    public const OP_SYSTEM = 1;
    public const OP_STUFF = 2;

    public const DELETE_FORBIDDEN_REMOVE = 0;
    public const DELETE_FORBIDDEN_SEARCH = 1;
    public const DELETE_FORBIDDEN_CHAT = 2;
    public const DELETE_FORBIDDEN_LOGIN = 3;
    public const DELETE_FORBIDDEN_ORDER_REMOVE = 10;
    public const DELETE_FORBIDDEN_ORDER = 11;

    public const FORBIDDEN_SOURCE_USER_REAUDIT = 'risk_check';

    public static $deleted_arr = [
        self::DELETE_FORBIDDEN_REMOVE => '解封',
        self::DELETE_FORBIDDEN_SEARCH => '禁搜',
        self::DELETE_FORBIDDEN_CHAT => '禁聊',
        self::DELETE_FORBIDDEN_LOGIN => '禁登录',
        self::DELETE_FORBIDDEN_ORDER_REMOVE => '解除禁止接单',
        self::DELETE_FORBIDDEN_ORDER => '禁止接单',
    ];

    public static $op_arr = [
        self::OP_SYSTEM => '系统',
        self::OP_STUFF => '人工',
    ];

    public static $source_arr = [
        '' => '系统',
        'user_list' => '用户列表',
        'sensitive_chat' => '敏感内容会话',
        'risk_chat' => '风险账号会话',
        'high_risk' => '高风险会话',
        'risk_check' => '风险用户审核',
        'check' => '用户审核',
        'report' => '举报',
        'user_nick' => '用户昵称签名',
        'user_icon' => '用户头像',
        'user_photo' => '用户形象照片',
        'circle_verify' => '朋友圈审核',
        'circle_comment' => '朋友圈评论',
        'circle_report' => '朋友圈举报',
        'xs_user_profile' => '用户昵称/签名',
        'xs_user_profile1' => '用户头像',
        'xs_user_photos' => '用户形象照',
        'xs_chat_message' => '私聊/群聊',
        'social_risk' => '社交风控',
        'official_account' => '官方账号管理',
        'alloo_live' => 'alloo直播',
        'app_super' => 'app内超管',
        'audit_report' => '消息举报'
    ];

    public static $displayMacneed = [
        '0' => '不封禁设备',
        '1' => '封禁设备',
    ];

    public static $displayMacneedphone = [
        '0' => '不同步安全手机号下的所有帐号',
        '1' => '同步安全手机号下的所有帐号',
    ];

    public static $displayDuration = [
        '7200' => '2小时',
        '14400' => '4小时',
        '28800' => '8小时',
        '43200' => '12小时',
        '86400' => '一天',
        '259200' =>  '三天',
        '604800' => '一周',
        '2592000' => '一个月',
        '315360000' =>  '永久',
    ];

    public static $displayGodReason = [
        '扰乱平台秩序',
        '恶意刷单',
        '加入/怂恿他人加入竞争平台',
        '越过平台收取现金单',
        '泄露闲时数据及用户隐私',
        '发布色情信息',
        '恶意诋毁平台',
        '恶意引导用户至第三方平台',
        '发布诈骗信息',
        '发布反动、政治类违法言论',
        '发布第三方广告',
        '真人与图片严重不符',
        '服务与水平严重不符',
        '欺诈用户导致服务未完成',
        '恶性竞争或恶意营销',
        '向用户索要订单外的额外费用',
        '要求用户先确认完成或好评后才服务',
        '未按照订单时间和时长服务用户',
        '侮辱谩骂骚扰用户',
        '服务迟到早退私自离开等',
        '服务不专心频繁做其他事情',
        '真人或视频服务时形象邋遢、暴露',
        '同一用户禁止多帐号登录',
        '昵称含有广告，色情或违法信息',
        '个人简介含有广告，色情或违法信息',
        '发布毒品、博彩、投注等违法信息',
        '强制用户下多倍单',
        '不按资质及订单要求服务',
        '用户下单后不主动回复用户',
        '聊天室里对老板态度恶劣',
        '在聊天室辱骂/攻击他人',
        '在聊天室进行低俗/色情表演',
        '在聊天室播放低俗/色情歌曲或配音',
        '在聊天室说低俗/色情擦边词语',
        '在聊天室收到打赏不提供服务',
        '在聊天室滥用踢人/禁麦等管理权限',
        '盗用他人信息',
        '冒充官方账号',
        '个人资料信息与身份认证信息不一致',
        '个人头像是网红/明星等网络来源图片',
        '买卖礼物',
        '钻石套现',
        '套现诈骗',
        '在聊天室违规表演',
        '技能审核通过',
        '系统误封',
        '客服误封',
        '大R酌情解封',
        '业务部门合作需要提前解封',
        '境外诈骗团伙',
        '金额异常',
        '视频通话消极对待(黑屏/真人未出境)',
    ];

    public static $displayReason = [
        '扰乱平台秩序',
        '越过平台走现金单',
        '发布色情信息',
        '发布诈骗信息',
        '发布反动、政治类违法言论',
        '发布第三方广告',
        '要挟大神提供订单以外的服务',
        '协助大神恶意刷单',
        '损害平台利益',
        '泄露他人隐私',
        '恶意发送第三方联系方式',
        '侮辱谩骂骚扰用户',
        '发布恶意链接',
        '发布引起观看不适的内容',
        '同一用户禁止多帐号登录',
        '昵称含有广告，色情或违法信息',
        '个人简介含有广告，色情或违法信息',
        '发布毒品、博彩、投注等违法信息',
        '多账号恶意扰乱平台秩序',
        '恶意下单使大神降权',
        '在聊天室辱骂/攻击他人',
        '在聊天室进行低俗/色情表演',
        '在聊天室播放低俗/色情歌曲或配音',
        '在聊天室说低俗/色情擦边词语',
        '在聊天室收到打赏不提供服务',
        '在聊天室滥用踢人/禁麦等管理权限',
        '盗用他人信息',
        '冒充官方账号',
        '个人资料信息与身份认证信息不一致',
        '个人头像是网红/明星等网络来源图片',
        '买卖礼物',
        '钻石套现',
        '套现诈骗',
        '在聊天室违规表演',
        '系统误封',
        '客服误封',
        '大R酌情解封',
        '业务部门合作需要提前解封',
        '境外诈骗团伙',
        '金额异常',
        '视频通话消极对待(黑屏/真人未出境)',
    ];
}
