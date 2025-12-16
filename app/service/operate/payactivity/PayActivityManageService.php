<?php

namespace Imee\Service\Operate\Payactivity;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsTopUpActivityMgr;

class PayActivityManageService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsTopUpActivityMgr::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1,$params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        return $list;
    }

    public function create(array $params): array
    {
        $info = XsTopUpActivityMgr::findOneByWhere([['bigarea_id', '=', $params['bigarea_id']]]);
        if ($info) {
            throw new ApiException(ApiException::MSG_ERROR, '当前大区已存在，请勿重复添加');
        }
        $data = [
            'bigarea_id'  => $params['bigarea_id'],
            'status'      => $params['status'],
            'modify_time' => time(),
        ];

        return XsTopUpActivityMgr::add($data);
    }

    public function modify(array $params): array
    {
        $info = XsTopUpActivityMgr::findOneByWhere([['bigarea_id', '=', $params['bigarea_id']]]);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前大区配置不存在');
        }

        $data = [
            'status' => $params['status']
        ];

        return XsTopUpActivityMgr::edit($info['id'], $data);
    }

    private function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }

        return $conditions;
    }
}