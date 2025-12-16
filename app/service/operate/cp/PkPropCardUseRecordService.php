<?php

namespace Imee\Service\Operate\Cp;

use Imee\Models\Xs\XsPropCardUseLog;
use Imee\Models\Xs\XsUserPropCard;
use Imee\Service\Helper;

class PkPropCardUseRecordService
{
    public function getListAndTotal(array $params): array
    {
        $list = XsPropCardUseLog::getListAndTotal($this->getConditions($params), '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $cardList = XsUserPropCard::getBatchCommon(Helper::arrayFilter($list['data'], 'user_card_id'), ['id', 'prop_card_id']);
        foreach ($list['data'] as &$item) {
            $extendData = $item['extend_data'] ? @json_decode($item['extend_data'], true) : [];

            $item['pk_id'] = $extendData['pk_id'] ?: '';
            $item['prop_card_id'] = $cardList[$item['user_card_id']]['prop_card_id'] ?? 0;
            $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
        }
        return $list;
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['card_type', 'IN', [XsPropCardUseLog::TYPE_PK_PROP_CARD_ADD, XsPropCardUseLog::TYPE_PK_PROP_CARD_MAG]]
        ];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86399];
        }

        return $conditions;
    }
}