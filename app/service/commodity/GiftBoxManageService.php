<?php

namespace Imee\Service\Commodity;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xsst\XsstCommodityOperationLog;
use Imee\Models\Xsst\XsstGiftBox;
use Imee\Service\Helper;
use Imee\Service\Lesscode\StatusService;

class GiftBoxManageService
{
    public function getList(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $dir = array_get($params, 'dir', 'desc');
        $sort = array_get($params, 'sort', 'cid');
        $conditions = $this->conditions($params);

        if ($sort == 'price') {
            $list = $this->getListByPrice($conditions, $dir == 'desc' ? SORT_DESC : SORT_ASC, $page, $limit);
        } else {
            $list = XsstGiftBox::getListAndTotal($conditions, '*', $sort . ' ' . $dir, $page, $limit);
        }

        if (empty($list['data'])) {
            return $list;
        }

        $cids = Helper::arrayFilter($list['data'], 'cid');
        $gids = Helper::arrayFilter($list['data'], 'gid');

        $commodityList = XsCommodityAdmin::getListByOcid($cids, 'ocid, image, mark');
        $giftList = XsGift::getBatchCommon($gids, ['id', 'price']);
        foreach ($list['data'] as &$item) {
            $item['is_modify'] = XsstGiftBox::$isModifyMap[$item['is_modify']] ?? '';
            $item['type'] = $this->getType($item['type']);
            $item['sub_type'] = $this->getSubType($item['sub_type']);
            $item['bigarea_id'] = $this->getBigAreaName($item['bigarea_id']);
            $item['image'] = Helper::getHeadUrl($commodityList[$item['cid']]['image'] ?? '');
            $item['mark'] = $commodityList[$item['cid']]['mark'] ?? '';
            $item['price'] = $giftList[$item['gid']]['price'] ?? 0;
            $item['dateline'] = $item['dateline'] > 0 ? Helper::now($item['dateline']) : '';
        }

        return $list;
    }

    private function getListByPrice(array $conditions, $order, $page = 1, $limit = 15): array
    {
        $lists = XsstGiftBox::getListByWhere($conditions, '*', '', 10000);
        $allGid = array_values(array_unique(array_column($lists, 'gid')));
        $giftList = XsGift::getBatchCommon($allGid, ['id', 'price']);

        foreach ($lists as &$rec) {
            $rec['price'] = isset($giftList[$rec['gid']]['price']) ? $giftList[$rec['gid']]['price'] : 0;
        }
        $priceArr = array_column($lists, 'price');
        array_multisort($priceArr, $order, $lists);

        return ['data' => array_slice($lists, ($page - 1) * $limit, $limit), 'total' => count($lists)];
    }

    private function conditions(array $params): array
    {
        $conditions = [
            ['state', '=', XsstGiftBox::IS_STATE_YES]
        ];

        if (!empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }

        if (!empty($params['gid'])) {
            $conditions[] = ['gid', '=', $params['gid']];
        }

        if (!empty($params['name'])) {
            $conditions[] = ['name', 'like', "%{$params['name']}%"];
        }

        if (!empty($params['type'][0])) {
            $conditions[] = ['type', 'FIND_IN_SET', $params['type'][0]];

            if (!empty($params['type'][1])) {
                $conditions[] = ['sub_type', 'FIND_IN_SET', $params['type'][1]];
            }
        }

        if (!empty($params['is_modify'])) {
            $conditions[] = ['is_modify', '=', $params['is_modify']];
        }

        if (!empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', 'FIND_IN_SET', $params['bigarea_id']];
        }

        return $conditions;
    }

    private function getType(string $type): string
    {
        $string = '';
        if (empty($type)) {
            return $string;
        }

        foreach (explode(',', $type) as $type) {
            $string .= (XsstGiftBox::$typeMap[$type] ?? '') . ',';
        }

        return rtrim($string, ',');
    }

    private function getSubType(string $subType): string
    {
        $string = '';
        if (empty($subType)) {
            return $string;
        }

        foreach (explode(',', $subType) as $type) {
            $string .= (XsstGiftBox::$subTypeMap[$type] ?? '') . ',';
        }

        return rtrim($string, ',');
    }

    private function getBigAreaName(string $bigAreaId): string
    {
        $string = '';
        if (empty($bigAreaId)) {
            return $string;
        }

        $bigAreaList = XsBigarea::getAllNewBigArea();

        foreach (explode(',', $bigAreaId) as $id) {
            $string .= ($bigAreaList[$id] ?? '') . ',';
        }

        return rtrim($string, ',');
    }

    public static function initGiftBoxData()
    {
        $commodityList = XsCommodityAdmin::getListByWhere([
            ['type', '=', 'gift'],
            ['state', '=', XsCommodityAdmin::STATE_PASS],
            ['can_opened_by_box', '=', 1]
        ], '*', 'cid desc');

        $now = time();

        foreach (array_chunk($commodityList, 50) as $listChunk) {
            $giftList = XsGift::getBatchCommon(Helper::arrayFilter($listChunk, 'ext_id'), ['id', 'price']);
            $insert = [];
            foreach ($listChunk as $commodity) {
                $price = $giftList[$commodity['ext_id']]['price'] ?? 0;
                $type = self::setType($price);
                $tmp = [
                    'cid'          => $commodity['ocid'],
                    'name'         => $commodity['name'],
                    'gid'          => $commodity['ext_id'],
                    'type'         => $type['type'],
                    'sub_type'     => $type['sub_type'],
                    'dateline'     => self::setBoxOpenAuditTime($commodity['cid']),
                    'is_modify'    => self::setIsModify($commodity['ocid']),
                    'bigarea_id'   => self::setBigArea($commodity['excludes']),
                    'created_time' => $now,
                    'updated_time' => $now,
                ];
                $insert[] = $tmp;
            }
            XsstGiftBox::addBatch($insert, 'REPLACE');
        }
    }

    public static function setType(float $price): array
    {
        $subTypeArray = $typeArray = [];
        $isBool = false;
        foreach (XsstGiftBox::$typePriceMap as $type => $typePriceArr) {
            if (in_array($price, $typePriceArr)) {
                $isBool = true;
                $subTypeArray[] = $type;
                if (in_array($type, XsstGiftBox::$boxTypeId)) {
                    $typeArray[] = XsstGiftBox::BOX_TYPE;
                } else if (in_array($type, XsstGiftBox::$blindBoxTypeId)) {
                    $typeArray[] = XsstGiftBox::BLIND_BOX_TYPE;
                }
            }
        }

        if (!$isBool) {
            $subTypeArray = [XsstGiftBox::OTHER_SUB_TYPE];
            $typeArray = [XsstGiftBox::OTHER_TYPE];
        }

        return ['sub_type' => implode(',', $subTypeArray), 'type' => implode(',', array_unique($typeArray))];
    }

    public static function setIsModify(int $cid): int
    {
        $isModify = XsstGiftBox::IS_MODIFY_YES;

        if (in_array($cid, XsstGiftBox::$isModifyNoCid)) {
            $isModify = XsstGiftBox::IS_MODIFY_NO;
        }

        return $isModify;
    }

    public static function setBigArea(string $excludes): string
    {
        $bigAreaIdArr = XsBigarea::getBigAreaIdByExcludes($excludes);

        return $bigAreaIdArr ? implode(',', $bigAreaIdArr) : '';
    }

    private static function setBoxOpenAuditTime($cid): int
    {
        $logs = XsstCommodityOperationLog::getListByWhere([
            ['cid', '=', $cid],
            ['type', '=', XsstCommodityOperationLog::TYPE_REVIEW_PASS],
        ], 'content, dateline', 'id desc');

        if (empty($logs)) {
            return 0;
        }

        foreach ($logs as $log) {
            $canOpenedByBox = json_decode($log['content'], true)['commodity']['can_opened_by_box'] ?? 0;
            if ($canOpenedByBox) {
                return $log['dateline'];
            }
        }

        return 0;
    }

    public function getTypeMap()
    {
        $map = [];

        foreach (XsstGiftBox::$typeMap as $key => $value) {
            $tmp = [
                'label'    => $value,
                'value'    => $key,
                'children' => []
            ];

            $subTypeId = [];
            if ($key == XsstGiftBox::BLIND_BOX_TYPE) {
                $subTypeId = XsstGiftBox::$blindBoxTypeId;
            } else if ($key == XsstGiftBox::BOX_TYPE) {
                $subTypeId = XsstGiftBox::$boxTypeId;
            }

            foreach (XsstGiftBox::$subTypeMap as $subKey => $subValue) {
                if (in_array($subKey, $subTypeId)) {
                    $tmp['children'][] = [
                        'label' => $subValue,
                        'value' => $subKey
                    ];
                }
            }

            $map[] = $tmp;
        }

        return $map;
    }

    public function getIsModifyMap()
    {
        return StatusService::formatMap(XsstGiftBox::$isModifyMap, 'label,value');
    }
}