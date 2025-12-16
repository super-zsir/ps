<?php

namespace Imee\Models\Xs;

class XsChatroomAuctionConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const PLAY_TYPE_AUCTION = 'auction';
    const PLAY_TYPE_THEONE = 'theone';
    const PLAY_TYPE_CONCERT = 'concert';

    /**
     * 批量获取房间相关配置
     * @param array $ridArray
     * @return array
     */
    public static function getListByRidBatch(array $ridArray): array
    {
        if (empty($ridArray)) {
            return [];
        }

        $list = self::getListByWhere([['rid', 'IN', $ridArray]]);
        if (empty($list)) {
            return $list;
        }

        $configList = [];

        foreach ($list as $item) {
            if (isset($configList[$item['rid']])) {
                $configList[$item['rid']][] = $item;
                continue;
            }

            $configList[$item['rid']] = [$item];
        }

        return $configList;
    }
}