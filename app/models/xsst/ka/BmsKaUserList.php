<?php

namespace Imee\Models\Xsst\Ka;

use Imee\Models\Xsst\BaseModel;
use Imee\Models\Xs\XsUserExp;

class BmsKaUserList extends BaseModel
{
    protected static $primaryKey = 'uid';
    protected $allowEmptyStringArr = ['country'];

    const KA_SOURCE = [
        'invite'    => 1, //邀请码
        'expLv'     => 2, //财富等级
        'create'    => 3, //人工创建
    ];

    const KA_BUILD_TYPE_WECHAT = '1';
    const KA_BUILD_TYPE_LINE = '2';
    const KA_BUILD_TYPE_WHATSAPP = '3';
    const KA_BUILD_TYPE_ZALO = '4';
    const KA_BUILD_TYPE_FB = '5';
    const KA_BUILD_TYPE_SNAPCHAT = '6';
    const KA_BUILD_TYPE_KAKAO = '7';
    const KA_BUILD_TYPE_GMAIL = '8';
    const KA_BUILD_TYPE_EW = '9';
    const KA_BUILD_TYPE_Telegram = '10';

    const KA_BUILD_TYPE_MAP = [
        self::KA_BUILD_TYPE_WECHAT   => 'Wechat',
        self::KA_BUILD_TYPE_LINE     => 'Line',
        self::KA_BUILD_TYPE_WHATSAPP => 'Whatsapp',
        self::KA_BUILD_TYPE_ZALO     => 'Zalo',
        self::KA_BUILD_TYPE_FB       => 'Facebook',
        self::KA_BUILD_TYPE_SNAPCHAT => 'Snapchat',
        self::KA_BUILD_TYPE_KAKAO    => 'Kakao',
        self::KA_BUILD_TYPE_GMAIL    => 'Gmail',
        self::KA_BUILD_TYPE_EW       => 'Enterprise WeChat',
        self::KA_BUILD_TYPE_Telegram => 'Telegram',
    ];

    const BUILD_AL_STATUS_WAIT = 0;     // 未建联
    const BUILD_AL_STATUS_DIRECT = 1;   // 已建联
    const BUILD_AL_STATUS_INDIRECT = 2; // 关联建联

    const BUILD_AL_STATUS_MAP = [
        self::BUILD_AL_STATUS_WAIT     => '未建联',
        self::BUILD_AL_STATUS_DIRECT   => '已建联',
        self::BUILD_AL_STATUS_INDIRECT => '关联建联',
    ];

    //各大区财富等级对应ka等级 左闭右开 start包含 end不包含
    const KA_TAG_AREA_MAP = [
        //英文大区
        'en' => [
            'S'         => ['start' => 110, 'end' => 999],
            'A'         => ['start' => 90, 'end' => 110],
            'B'         => ['start' => 80, 'end' => 90],
            'C'            => ['start' => 65, 'end' => 80],
            'pre-KA'    => ['start' => 50, 'end' => 65],
        ],
        //中文大区
        'cn' => [
            'S'         => ['start' => 110, 'end' => 999],
            'A'         => ['start' => 90, 'end' => 110],
            'B'         => ['start' => 80, 'end' => 90],
            'C'            => ['start' => 65, 'end' => 80],
            'pre-KA'    => ['start' => 50, 'end' => 65],
        ],
        //阿语大区
        'ar' => [
            'S'         => ['start' => 110, 'end' => 999],
            'A'         => ['start' => 90, 'end' => 110],
            'B'         => ['start' => 80, 'end' => 90],
            'C'            => ['start' => 65, 'end' => 80],
            'pre-KA'    => ['start' => 50, 'end' => 65],
        ],
        //韩语大区
        'ko' => [
            'S'         => ['start' => 110, 'end' => 999],
            'A'         => ['start' => 90, 'end' => 110],
            'B'         => ['start' => 80, 'end' => 90],
            'C'            => ['start' => 65, 'end' => 80],
            'pre-KA'    => ['start' => 50, 'end' => 65],
        ],
        //印尼大区
        'id' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //泰语大区
        'th' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //越南大区
        'vi' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //土耳其大区
        'tr' => [
            'S'         => ['start' => 110, 'end' => 999],
            'A'         => ['start' => 90, 'end' => 110],
            'B'         => ['start' => 80, 'end' => 90],
            'C'            => ['start' => 65, 'end' => 80],
            'pre-KA'    => ['start' => 50, 'end' => 65],
        ],
        //马来大区
        'ms' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //日语大区
        'ja' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //孟加拉大区
        'bn' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //印度大区
        'hi' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //巴基斯坦大区
        'ur' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //菲律宾大区
        'tl' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
        //马来华语区
        'mz' => [
            'S'         => ['start' => 90, 'end' => 999],
            'A'         => ['start' => 80, 'end' => 90],
            'B'         => ['start' => 65, 'end' => 80],
            'C'            => ['start' => 50, 'end' => 65],
            'pre-KA'    => ['start' => 40, 'end' => 50],
        ],
    ];

    const KA_TAG = [
        'S'         => 'S',
        'A'         => 'A',
        'B'         => 'B',
        'C'         => 'C',
        'pre-KA'    => 'pre-KA',
    ];

    //根据用户财富等级获取用户ka tag
    public static function getKaTag($uid, $area, $app_id = APP_ID)
    {
        if (empty($uid) || empty($area)) {
            return '';
        }
        //财富等级
        $expLv = XsUserExp::findFirst([
            'conditions' => 'uid = :uid: AND app_id = :app_id:',
            'bind' => ['uid' => $uid, 'app_id' => $app_id],
            'columns' => 'lv'
        ]);
        $expLv = $expLv ? $expLv->lv : 0;
        $tagArr = self::KA_TAG_AREA_MAP[$area];
        if (empty($tagArr)) {
            return '';
        }
        $kaTag = '';
        foreach ($tagArr as $tag => $range) {
            $start = $range['start'];
            $end = $range['end'];
            if ($expLv >= $start && $expLv < $end) {
                $kaTag = $tag;
                break;
            }
        }
        return $kaTag;
    }
}
