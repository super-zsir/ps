<?php

namespace Imee\Service\Commodity;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityRecommend;
use Imee\Service\StatusService;

class CommodityRecommendService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $cid = intval(array_get($params, 'cid', 0));

        $query = [['app_id', '=', APP_ID]];
        $cid && $query[] = ['cid', '=', $cid];
        $data = XsCommodityRecommend::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $cidNames = array_column($data['data'], 'cid');
        $cidNames = XsCommodity::getListByWhere([['cid', 'in', array_values(array_unique($cidNames))]], 'cid, name');
        $cidNames = array_column($cidNames, 'name', 'cid');

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $updateline = array_get($rec, 'updateline', 0);
            $cid = array_get($rec, 'cid', 0);

            $rec['name'] = array_get($cidNames, $cid, '');
            $rec['dateline'] = $dateline ? date("Y-m-d H:i:s", $dateline) : "-";
            $rec['updateline'] = $updateline ? date("Y-m-d H:i:s", $updateline) : "-";
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = XsCommodityRecommend::add($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsCommodityRecommend::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $update = [];
        $data = $this->validateAndFormatData($params);
        foreach ($data as $k => $v) {
            if ($v != array_get($setting, $k)) {
                $update[$k] = $v;
            }
        }

        if (count($update)) {
            list($flg, $rec) = XsCommodityRecommend::updateByWhere([['id', '=', $id]], $update);

            return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
        }
        return [false, '数据不需要更新'];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsCommodityRecommend::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $flg = XsCommodityRecommend::deleteById($id);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }

    private function validateAndFormatData($params): array
    {
        $id = intval(array_get($params, 'id', 0));

        $cid = trim(array_get($params, 'cid', ''));
        $type = trim(array_get($params, 'type', ''));
        $weight = intval(array_get($params, 'weight', 0));

        $res = XsCommodity::findOne($cid);
        if (empty($res)) {
            throw new ApiException(ApiException::MSG_ERROR, '无此物品');
        }

        $data = [
            'app_id'     => APP_ID,
            'cid'        => $cid,
            'type'       => $type,
            'weight'     => $weight,
            'updateline' => time(),
        ];
        if (empty($id)) {
            $data['dateline'] = time();
        }
        return $data;
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(XsCommodityRecommend::$typeMap);
    }
}