<?php

namespace Imee\Models\Nsq;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Redis\CsmsRedis;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSettings;

class CsmsNsq
{


    public static $choice = [
        'xs_user_name',
        'xs_user_sign',
        'xs_user_icon'
    ];

    /**
     * 添加机审队列
     * @param $data
     */
    public static function csmsCheck($data)
    {
        NsqClient::publish(NsqConstant::TOPIC_CSMS_CHECK, [
            'cmd'  => 'csms.check',
            'data' => $data
        ], 3);
    }

    /**
     * csmspush
     * @param array $data
     * @return bool
     */
    public static function csmsPush($data = [])
    {

        // 推送到PT 的数据，需要补充 area  language  sex
        $data['area'] = XsUserBigarea::getUserArea($data['uid']);
        $data['language'] = XsUserSettings::getLanguage($data['uid']);
        $data['sex'] = XsUserProfile::getUserSex($data['uid']);
        $data['app_id'] = APP_ID;

        CsmsRedis::csmsPush($data);
        // 老后台开始 停止进入数据
        return false;

        // redis 来一份 老后台不停止
//        if(in_array($data['choice'], self::$choice)){
//            CsmsRedis::csmsPush($data);
//            // 老后台开始 停止进入数据
//            return false;
//        }
//
//
//        return NsqClient::csmsPublish(NsqConstant::TOPIC_CSMS_NSQ, [
//            'cmd'  => 'csms.push',
//            'data' => $data
//        ]);
    }
}