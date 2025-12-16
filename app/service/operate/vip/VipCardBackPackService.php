<?php

namespace Imee\Service\Operate\Vip;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserPropCard;
use Imee\Service\Helper;
use Imee\Service\Operate\Relieveforbiddencard\RelieveForbiddenCardSearchService;
use Imee\Service\Operate\VipsendService;
use Imee\Service\StatusService;

class VipCardBackPackService
{
    public function getList(array $params): array
    {
        $list = XsUserPropCard::getListJoinTable(
            $this->getConditions($params),
            $this->getJoinConditions(),
            $this->getColumns(),
            'count(*) as cnt',
            'u.dateline desc, hold_num desc',
            $params['page'] ?? 1,
            $params['limit'] ?? 15
        );
        if ($list['total'] == 0) {
            return [];
        }

        foreach ($list['data'] as &$item) {
            $item['extend'] = @json_decode($item['extend'], true);
            $item['vip_level'] = $item['extend']['level'] ?? '';
        }

        return $list;
    }

    private function getColumns(): array
    {
        return [
            'u.id',
            'u.uid',
            'u.send_times',
            'b.bigarea_id',
            'u.prop_card_id',
            'c.extend',
            'u.prop_card_type',
            'u.num as hold_num'
        ];
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['u.num', '<>', 0]
        ];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['u.uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['b.bigarea_id', '=', $params['bigarea_id']];
        }
        if (isset($params['prop_card_type']) && !empty($params['prop_card_type'])) {
            $conditions[] = ['u.prop_card_type', '=', $params['prop_card_type']];
        } else {
            $conditions[] = ['u.prop_card_type', 'IN', [XsPropCardConfig::TYPE_VIP_CARD, XsPropCardConfig::TYPE_CAN_SEND_VIP_CARD]];
        }

        return $conditions;
    }

    private function getJoinConditions(): array
    {
        return [
            [
                'class' => XsPropCard::class,
                'condition' => 'u.prop_card_id = c.id',
                'table' => 'c'
            ],
            [
                'class' => XsUserBigarea::class,
                'condition' => 'u.uid = b.uid',
                'table' => 'b'
            ]
        ];
    }

    public function getPropCardTypeMap(): array
    {
        return StatusService::formatMap(XsPropCardConfig::$typeBackPackMaps, 'label,value');
    }

    public function recycle(array $params): array
    {
        if (in_array(($params['vip_level'] ?? 0), [7, 8])) {
            if (!VipsendService::hasVip7Purview()) {
                throw new ApiException(ApiException::MSG_ERROR, sprintf('您没有权限回收VIP%s卡', $params['vip_level']));
            }
        }
        $card = XsUserPropCard::findOne($params['id']);
        $params['card_type'] = $card['prop_card_type'] ?? 0;
        // 业务研发要求这里传背包VIP卡id。传vip卡自身id，会定位不到背包里哪张卡
        $params['prop_card_id'] = $params['id'];
        return (new RelieveForbiddenCardSearchService())->recover($params);
    }
}