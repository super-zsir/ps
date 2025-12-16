<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsBmsOperateHistory extends BaseModel
{
    const PRICE_LEVEL = 'user_price_level';
    const PRETTY_NUM = 'user_pretty_num';
    const ROOM_TOP = 'room_top';
    const BIGRAEA = 'xs_bigarea';
    const GLOBAL_CONFIG = 'xs_global_config';
    // Greedy玩法配置-修改权重日志标识（此处仅是标识）
    const GLOBAL_CONFIG_WEIGHT = 'xs_global_config_weight';
    // Greedy玩法配置-修改参数日志标识（此处仅是标识）
    const GLOBAL_CONFIG_PARAMS = 'xs_global_config_params';
    public static $source = [
        'bbc_banner_settings'        => 1,
        'bms_user_code_reset'        => 2,
        'chat_interact_suggestion'   => 3,
        'bbc_campaign_user_label'    => 4,
        'quick_say_hi_template'      => 5,
        'quick_say_hi_template_rule' => 6,
        'bbc_gift_skin'              => 7,
        'bbc_web_title'              => 8,
        self::PRICE_LEVEL            => 9,
        self::ROOM_TOP               => 10,
        self::PRETTY_NUM             => 11,
        self::BIGRAEA                => 12,
        self::GLOBAL_CONFIG          => 13,
        self::GLOBAL_CONFIG_WEIGHT   => 14,
        self::GLOBAL_CONFIG_PARAMS   => 15,
    ];

    public static function insertRows(string $table, int $sid, array $data, int $uid): void
    {
        $source = self::$source[$table] ?? 0;
        if (empty($source)) return;

        $insertData = [
            'source'       => $source,
            'sid'          => $sid,
            'content'      => json_encode($data),
            'update_uid'   => $uid,
            'update_uname' => Helper::getAdminName($uid),
            'dateline'     => time(),
        ];

        self::add($insertData);
    }

    public static function getLatestUpdateLog(string $table, array $ids): array
    {
        $source = self::$source[$table] ?? 0;
        if ($source == 0 || empty($ids)) return [];
        $ids = implode(',', $ids);
        $sql = "SELECT sid,update_uid,dateline FROM xs_bms_operate_history WHERE id IN (
    				SELECT MAX(id) FROM xs_bms_operate_history WHERE `source`={$source} AND sid IN({$ids}) GROUP BY sid
    			)";
        $logs = \Imee\Service\Helper::fetch($sql, null, 'xsdb');
        if (empty($logs)) return [];
        foreach ($logs as &$log) {
            $log['update_uname'] = Helper::getAdminName($log['update_uid']);
            $log['dateline'] = date('Y-m-d H:i', $log['dateline']);
        }
        return array_column($logs, null, 'sid');
    }

    /**
     * 获取历史记录
     * @param string $table
     * @param int $sid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getHistoryBySid(string $table, int $sid, int $page, int $limit): array
    {
        $source = self::$source[$table] ?? 0;
        if ($source == 0 || $sid < 1) return [];

        $conditions = [
            ['source', '=', $source],
            ['sid', '=', $sid]
        ];

        return self::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
    }
}