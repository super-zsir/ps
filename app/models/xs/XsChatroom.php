<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsChatroom extends BaseModel
{
    protected static $primaryKey = 'rid';

    const PROPERTY_BUSINESS = 'business';
    const PROPERTY_VIP = 'vip';
    const PROPERTY_FLEET = 'fleet';
    const PROPERTY_PRIVATE = 'private';
    const PROPERTY_UNION = 'union';
    const PROPERTY_LIVEROOM = 'liveroom';
    const PROPERTY_EXCEPTION = 'exception';

    public static $propertyAllMap = [
        self::PROPERTY_BUSINESS  => '商业',
        self::PROPERTY_VIP       => '爵位',
        self::PROPERTY_FLEET     => '家族',
        self::PROPERTY_PRIVATE   => '个人',
        self::PROPERTY_UNION     => '联盟',
        self::PROPERTY_LIVEROOM  => '视频',
        self::PROPERTY_EXCEPTION => '异常',
    ];

    // 属性(表单需要)
    public static $propertyFormArray = [self::PROPERTY_BUSINESS, self::PROPERTY_VIP, self::PROPERTY_FLEET, self::PROPERTY_PRIVATE];

    const MODE_LOCK = 'lock';
    const MODE_AUTO = 'auto';

    public static $modeMap = [
        self::MODE_LOCK => '抱人上麦',
        self::MODE_AUTO => '自由上麦',
    ];

    const NINE_YES = 1;
    const NINE_NO = 0;

    public static $nineMap = [
        self::NINE_YES => '9麦位',
        self::NINE_NO  => '8麦位',
    ];

    const DELETED_WAIT_ADD = -1;
    const DELETED_NORMAL = 0;
    const DELETED_CLOSE = 1;
    const DELETED_FORBIDDEN = 2;

    public static $deletedMap = [
        self::DELETED_WAIT_ADD  => '待创建',
        self::DELETED_NORMAL    => '正常',
        self::DELETED_CLOSE     => '关闭',
        self::DELETED_FORBIDDEN => '封禁',
    ];

    // 语音房类型
    const VIP_PROPERTY = 'vip';

    const TYPE_NORMAL = 'normal';
    const TYPE_ORDER = 'order';
    const TYPE_AUTO = 'auto';
    const TYPE_RADIO = 'radio';
    const TYPE_RADIO_DEFEND = 'radio-defend';
    const TYPE_CP = 'cp';
    const TYPE_CP_LOVE = 'cp-love';
    const TYPE_JOY = 'joy';
    const TYPE_PRIVATE = 'private';
    const TYPE_LIVE = 'live';

    public static $typesArray = [
        self::TYPE_NORMAL,
        self::TYPE_ORDER,
        self::TYPE_AUTO,
        self::TYPE_RADIO,
        self::TYPE_RADIO_DEFEND,
        self::TYPE_CP,
        self::TYPE_CP_LOVE,
        self::TYPE_JOY,
        self::TYPE_PRIVATE,
        self::TYPE_LIVE,
    ];

    const SEX_NULL = 0;
    const SEX_MALE = 1;
    const SEX_FEMALE = 2;

    public static $sexMap = [
        self::SEX_NULL   => '空',
        self::SEX_MALE   => '男',
        self::SEX_FEMALE => '女',
    ];

    const TIME_TYPE_NO = 0;
    const TIME_TYPE_YES = 1;

    public static $timeTypeMap = [
        self::TIME_TYPE_NO  => '不开放',
        self::TIME_TYPE_YES => '指定时间开放',
    ];

    // 房间默认封面地址
    const DEFAULT_ICON = 'static/link/24042615222899.png';

    // 关闭房间key 前缀
    const CLOSE_KEY_PREFIX = 'room_closed_tips';

    /**
     * 根据rid批量获取信息
     * @param array $ridArr
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getInfoBatch($ridArr = [], $fieldArr = ['rid', 'uid'])
    {
        if (empty($ridArr)) {
            return [];
        }
        if (!in_array('rid', $fieldArr)) {
            $fieldArr[] = 'rid';
        }

        $data = static::find(array(
            'columns'    => implode(',', $fieldArr),
            'conditions' => "rid in ({rids:array})",
            'bind'       => array(
                'rids' => $ridArr,
            ),
        ))->toArray();
        if (empty($data)) {
            return array();
        }

        return array_column($data, null, 'rid');
    }

    public static function getInfoByUidAndProperty($uid, $property)
    {
        return self::findOneByWhere([
            ['uid', '=', $uid],
            ['property', '=', $property]
        ]);
    }

    /**
     * 根据uids和属性获取房间列表
     * @param array $uidArray
     * @param string $property
     * @return array
     */
    public static function getListByUidArrayAndProperty(array $uidArray, string $property): array
    {
        if (empty($uidArray) || empty($property)) {
            return [];
        }

        $list = self::getListByWhere([
            ['uid', 'IN', $uidArray],
            ['property', '=', $property]
        ], 'rid, uid, deleted');

        return $list ? array_column($list, null, 'uid') : [];
    }

    public static function getAreaMap(): array
    {
        return [
            'other' => '其他',
            'tw'    => '台湾',
            'sg'    => '新加坡',
            'my'    => '马来西亚',
            'th'    => '泰国',
            'hk'    => '香港',
            'mo'    => '澳门',
            'vn'    => '越南',
            'kr'    => '韩国',
            'jo'    => '约旦',
            'ir'    => '伊朗',
            'ma'    => '摩洛哥',
            'tn'    => '突尼斯',
            'sd'    => '苏丹',
            'dz'    => '阿尔及利亚',
            'kw'    => '科威特',
            'eg'    => '埃及',
            'iq'    => '伊拉克',
            'ae'    => '阿联酋',
            'bh'    => '巴林',
            'lb'    => '黎巴嫩',
            'om'    => '阿曼',
            'qa'    => '卡塔尔',
            'sa'    => '沙特',
            'sy'    => '叙利亚',
            'mr'    => '毛里塔尼亚',
            'so'    => '索马里',
            'ye'    => '也门',
            'bl'    => '巴勒斯坦',
            'ly'    => '利比亚',
            'km'    => '科摩罗',
            'dj'    => '吉布提',
            'ph'    => '菲律宾',
            'us'    => '美国',
            'ca'    => '加拿大',
            'au'    => '澳大利亚',
            'gb'    => '英国',
            'ua'    => '乌克兰',
            'ru'    => '俄罗斯',
            'id'    => '印度尼西亚',
            'tr'    => '土耳其',
            'ja'    => '日本',
            'hi'    => '印度',
            'bn'    => '孟加拉',
            'ur'    => '巴基斯坦',
            'tl'    => '菲律宾',
        ];
    }

    public static function getWeekMap()
    {
        return [
            '1' => '一',
            '2' => '二',
            '3' => '三',
            '4' => '四',
            '5' => '五',
            '6' => '六',
            '7' => '七',
        ];
    }

    /**
     * 获取类型字段
     * @param $type
     * @return bool
     */
    public static function getTypesField($type): string
    {
        return in_array($type, self::$typesArray) ? 'types' : 'type';
    }

    /**
     * 获取房间所有类型types、type
     * @return array
     */
    public static function getTypeMap(): array
    {
        $list = self::getListByWhere([['property', '=', self::PROPERTY_BUSINESS]], 'types, type');
        if (empty($list)) {
            return $list;
        }

        $types = Helper::arrayFilter($list, 'types');
        $type = Helper::arrayFilter($list, 'type');

        $typeAll = array_values(array_unique(array_filter(array_merge($types, $type))));

        $map = [];

        foreach ($typeAll as $item) {
            $map[$item] = $item;
        }

        return $map;
    }
}