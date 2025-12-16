<?php

namespace Imee\Helper\Constant;

/**
 * STRATEGY_ID 取值源自规则中心配置
 */
class RiskConstant
{
	const RISK_USER_RULE_TYPES = [
		1 => '疑似机器人',
		2 => '昵称签名疑似广告',
		10 => '召回率1',
		11 => '当日被新打招呼超过次数',
		12 => '首页曝光无反馈',
		30 => '群发消息',
		31 => '短时间发送相同图片给多人',
		32 => '私聊多位陌生人',
		40 => '频繁多用户聊天',
	];

    const RISK_OP_TYPE_FORBIDDEN = 1;

    const RISK_USER_ALGORITHM_RULE_IDS = [33, 34, 35];//推荐组进入风险用户审核的规则ID

    const STRATEGY_ID_USER_REAUDIT = 1;//风险用户审核

    const STRATEGY_ID_PAYMENT = 2;//充值风控

    const STRATEGY_ID_ACCOUNT_SESSION = 3;//风险账号会话

    const STRATEGY_ID_LOGIN_EXCEPTION = 4;//登录异常

    const STRATEGY_ID_MALICE_REGISTER = 5;//恶意注册

    const STRATEGY_ID_APPLE_PAY = 6;//苹果支付风控


    //昵称签名组合违规,数据库默认
    const RISK_RULE_NAME_SIGN = 1;
    //聊天信息触发敏感词
    const RISK_RULE_SENSITIVE = 2;
    //连续发相同的消息(高频群发)
    const RISK_RULE_SAME = 3;
    //时间段内连续发消息给不同的人(高频骚扰用户)
    const RISK_RULE_MASS = 4;
    //频繁被房间踢出(聊天室封禁)
    const RISK_RULE_KICK = 5;
    //room-风险用户
    const RISK_RULE_ROOM_RISK_USER = 6;
    //room-风险昵称
    const RISK_RULE_ROOM_RISK_NAME = 7;
    //注册首次会话风险
    const RISK_REGISTER_FIRST_CHAT = 8;
    //间歇骚扰用户
    const RISK_MASS_CHAT = 9;
    //疑似养号
    const RISK_SUSPECTED_RAISE_ACCOUNT = 10;
    //支付风险
    const RISK_PAYMENT = 11;
    //间歇群发
    const RISK_RULE_INTERMITTENT_MASS = 12;
    //恶意发送联系方式
    const RISK_RULE_SEND_CONTACT_INFO = 13;
}
