<?php

namespace Imee\Service\Commodity;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCommodityGroup;

class CommodityGroupService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $data = XsCommodityGroup::getListAndTotal($this->getCondition($params), '*', 'group_id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i', $dateline) : '';
        }

        return $data;
    }

    public function getCondition($params): array
    {
        $id = intval(array_get($params, 'group_id', 0));
        $name = trim(array_get($params, 'name', ''));

        $query = [['app_id', '=', APP_ID]];
        $id && $query[] = ['group_id', '=', $id];

        if ($name) {
            $query[] = ['group_name', 'like', $name];
        }

        return $query;
    }

    public function getCount($params): int
    {
        return XsCommodityGroup::getCount($this->getCondition($params));
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = XsCommodityGroup::add($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'group_id');
        $setting = XsCommodityGroup::findOne($id);
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
            list($flg, $rec) = XsCommodityGroup::updateByWhere([['group_id', '=', $id]], $update);

            return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
        }
        return [false, '数据不需要更新'];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'group_id');
        $setting = XsCommodityGroup::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $flg = XsCommodityGroup::deleteById($id);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }

    public function import($data): array
    {
        if (!empty($data[0]) && $data[0]['group_name'] == XsCommodityGroup::$nameBigarea['group_name']) {
            unset($data[0]);
        }
        if (empty($data)) {
            return [false, '数据错误'];
        }

        $insertData = [];
        foreach ($data as $v) {
            $insertData[] = $this->validateAndFormatData($v);
        }

        list($flg, $rec,) = XsCommodityGroup::addBatch($insertData);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => $insertData] : $rec];
    }

    private function validateAndFormatData($params): array
    {
        $id = intval(array_get($params, 'group_id', 0));

        $data = [
            'updateline'       => time()
        ];

        foreach (array_keys(XsCommodityGroup::$nameBigarea) as $field) {
            $data[$field] = trim($params[$field] ?? '');
        }

        if (empty($id)) {
            $data['app_id'] = APP_ID;
            $data['dateline'] = time();
        }
        return $data;
    }
}