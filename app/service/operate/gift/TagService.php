<?php


namespace Imee\Service\Operate\Gift;


use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCommodityTag;
use Imee\Service\Helper;

class TagService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $data = XsCommodityTag::getListAndTotal($this->getCondition($params), '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'created_at', 0);
            $rec['created_at'] = $dateline ? date('Y-m-d H:i', $dateline) : '';
            $rec['icon_url'] = Helper::getHeadUrl($rec['icon']);
        }

        return $data;
    }

    public function getCondition($params): array
    {
        $name = trim(array_get($params, 'name', ''));

        $conditions = [];
        if ($name) {
            $conditions[] = ['name', 'like', $name];
        }

        return $conditions;
    }

    public function getCount($params): int
    {
        return XsCommodityTag::getCount($this->getCondition($params));
    }

    public function add($params, $appId = APP_ID): array
    {
        $data = [
            'app_id'     => $appId,
            'name'       => $params['name'],
            'icon'       => $params['icon'],
            'remark'     => $params['remark'] ?? '',
            'updated_at' => time(),
            'created_at' => time()
        ];
        list($flg, $rec) = XsCommodityTag::add($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsCommodityTag::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $update = [
            'name'       => $params['name'],
            'icon'       => $params['icon'],
            'remark'     => $params['remark'] ?? '',
            'updated_at' => time(),
        ];

        list($flg, $rec) = XsCommodityTag::updateByWhere([['id', '=', $id]], $update);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsCommodityTag::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $flg = XsCommodityTag::deleteById($id);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }
}