<?php

namespace Imee\Service\Operate\Cp;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsIntimateRelationPayConfig;
use Imee\Models\Xs\XsUserPurchaseRecord;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class RelationNumberPriceService
{
    public function getList(array $params): array
    {
        $list = XsIntimateRelationPayConfig::getListAndTotal([], '*', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 1);
        if (empty($list['data'])) {
            return $list;
        }

        $logs = BmsOperateLog::getFirstLogList('relationnumberprice', Helper::arrayFilter($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $item['admin_name'] = $logs[$item['id']]['operate_name'] ?? '';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
        }

        return $list;
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $bigAreaId = intval($params['bigarea_id'] ?? 0);
        $intimateRelationType = intval($params['intimate_relation_type'] ?? 0);
        $price = intval($params['price'] ?? 0);

        if (empty($id) || empty($bigAreaId) || empty($intimateRelationType) || empty($price)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Param error');
        }

        $config = XsIntimateRelationPayConfig::findOne($id);
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Config not exists');
        }

        $data = [
            'relation_type' => $intimateRelationType,
            'bigarea_id'    => $bigAreaId,
            'price'         => $price
        ];

        list($res, $msg) = (new PsService())->editIntimateRelationPayConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data, 'before_json'=> $config];
    }

    public function getRecordList(array $params): array
    {
        $conditions = [];
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])){
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }
        if (isset($params['uid']) && !empty($params['uid'])){
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86399];
        }


        $list = XsUserPurchaseRecord::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    public function getIntimateRelationTypeMap()
    {
        return StatusService::formatMap(XsIntimateRelationPayConfig::$intimateRelationTypeMap, 'label,value');
    }
}