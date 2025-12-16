<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsChatTeamPkDiamondRecord;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class TeamPkRecordService
{
    public static function formatList($recordList)
    {
        foreach ($recordList as &$v) {
            foreach (['dateline', 'start_time', 'end_time'] as $time) {
                if (isset($v[$time]) && !empty($v[$time])) {
                    $v[$time] = Helper::now(intval($v[$time]));
                }
            }
            $v['pk_start_end_time'] = $v['start_time'] . '~~' . $v['end_time'];
            $totalNum = $v['red_send_num'] + $v['blue_send_num'];
            $v['total_send_diamond'] = $v['red_send_diamond'] + $v['blue_send_diamond'];
            $v['red_rec_num'] = self::_getRecordModal($v['red_rec_num'], 'teampkrecordred' , $v['pk_id'], XsChatTeamPkDiamondRecord::TEAM_PK_RANK_TYPE_RED_REC);
            $v['blue_rec_num'] = self::_getRecordModal($v['blue_rec_num'], 'teampkrecordblue', $v['pk_id'], XsChatTeamPkDiamondRecord::TEAM_PK_RANK_TYPE_BLUE_REC);
            $v['total_send_num'] = self::_getRecordModal($totalNum, 'teampksendrecord', $v['pk_id'], XsChatTeamPkDiamondRecord::TEAM_PK_RANK_TYPE_RED_SEND);
        }
        return $recordList;
    }

    private static function _getRecordModal($title, $guid, $pkId, $type)
    {
        return [
            'title' => $title,
            'value' => $title,
            'type' => 'manMadeModal',
            'modal_id' => 'table_modal',
            'params' => [
                'guid' => $guid,
                'pk_id' => $pkId,
                'type' => $type
            ]
        ];
    }

    public static function getConditions(array $params): array
    {
        $conditions = [];

        return $conditions;
    }

}