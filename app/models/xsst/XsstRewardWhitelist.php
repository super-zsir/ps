<?php

namespace Imee\Models\Xsst;

class XsstRewardWhitelist extends BaseModel
{
    protected static $primaryKey = 'id';

    const TYPE_REWARD_SEND = 1;
    const TYPE_REWARD_SEND_AUDIT = 2;

    public static $typeMap = [
        self::TYPE_REWARD_SEND       => '奖励发放白名单',
        self::TYPE_REWARD_SEND_AUDIT => '奖励发放审核白名单',
    ];

    /**
     * 判断白名单是否存在后台用户
     * @param int $type
     * @param int $uid
     * @return array
     */
    public static function hasWhiteListUid(int $type, int $uid): array
    {
        return self::findOneByWhere([
            ['type', '=', $type],
            ['user_id', '=', $uid]
        ]);
    }
}