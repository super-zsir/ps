<?php

namespace Imee\Models\Xss;

class XssAutoService extends BaseModel
{
    public const SERVICE_CONSULTATION = 10000016;
    public const SERVICE_ORDER_APPEAL = 10000017;
     public const SERVICE_SUGGESTIONS = 10000018;
     public const SERVICE_CERTIFICATION = 10000019;
    public const SERVICE_AUDIT = 10000020;
    public const SERVICE_VIP = 10000022;

    // const NEW_VERSION_YES = 1;
    // const NEW_VERSION_NO = 0;

    // const SOURCE_MULTIPLE_UNMATCHED = 1;
    // const SOURCE_DIRECT = 2;
    // const SOURCE_MULTI = 3;
    // const SOURCE_AUTO = 4;

    public static $manualChatServiceConfig = [
        self::SERVICE_CONSULTATION => '客服咨询',
        self::SERVICE_ORDER_APPEAL => '订单申诉',
        self::SERVICE_AUDIT => '客服审核',
        self::SERVICE_VIP => '客服VIP',
    ];

     public static $serviceArray = array(
         self::SERVICE_CONSULTATION => '客服咨询',
         self::SERVICE_ORDER_APPEAL => '订单申诉',
         self::SERVICE_SUGGESTIONS => '客服建议',
         self::SERVICE_CERTIFICATION => '客服认证',
         self::SERVICE_AUDIT => '客服审核',
         self::SERVICE_VIP => '客服VIP',
     );

     public static $reasonArray = [
         'ok' => '已解答',
         'user_no_reply' => '无回应',
         'no_answer' => '暂无解答',
         'system_user_timeout' => '用户超时',
         'system_service_timeout' => '客服超时',
     ];

     public static $voteArray = [
         'none' => '未评价',
         'yes' => '满意',
         'no' => '不满意',
     ];
    

    // public static $chatSource = [
    //     self::SOURCE_MULTIPLE_UNMATCHED => '多次未匹配',
    //     self::SOURCE_DIRECT => '直接找人工',
    //     self::SOURCE_MULTI => '多轮对话',
    //     self::SOURCE_AUTO => '自动应答',
    // ];
}
