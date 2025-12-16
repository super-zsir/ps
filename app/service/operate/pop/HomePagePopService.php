<?php

namespace Imee\Service\Operate\Pop;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPopupsConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class HomePagePopService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);

        $list = XsPopupsConfig::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('homepagepop', $ids);
        $time = time();
        foreach ($list['data'] as &$item) {
            $item['effective_status'] = 1;
            if ($item['end_time'] > $time && $item['start_time'] < $time) {
                $item['effective_status'] = 2;
            } else if ($item['end_time'] < $time) {
                $item['effective_status'] = 3;
            }
            $item['icon'] = Helper::getHeadUrl($item['icon']);
            $item['start_time'] = Helper::now($item['start_time']);
            $item['end_time'] = Helper::now($item['end_time']);
            $item['pop_time'] = $item['start_time'] . '~~' . $item['end_time'];
            $item['dateline'] = Helper::now($item['dateline']);
            $item['admin_name'] = $logs[$item['id']]['operate_name'] ?? '-';
        }

        return $list;
    }

    public function modify(array $params)
    {
        $data = [
            'icon'      => $params['icon'] ?? '',
            'jump_url'  => $params['jump_url'],
            'deleted'   => XsPopupsConfig::DELETED_NO,
            'start_time'=> strtotime($params['start_time']),
            'end_time'  => strtotime($params['end_time']),
            'ordering'  => (int) $params['order'],
            'bigarea_id'=> (int) $params['bigarea_id'],
            'lv'        => (int) $params['lv'],
            'id'        => (int) $params['id'],
            'type'      => XsPopupsConfig::HOME_PAGE_TYPE,
        ];
        if ($data['start_time'] > $data['end_time']) {
            throw new ApiException(ApiException::MSG_ERROR, '开始时间不能大于结束时间');
        }

        [$res, $msg, $id] = (new PsService())->addPopupsConfig($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $id;
    }

    public function disable(int $id)
    {
        [$res, $msg] = XsPopupsConfig::edit($id, [
           'deleted' => XsPopupsConfig::DELETED_YES
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getConditions(array $params): array
    {
        $conditions = [
            ['type', '=', XsPopupsConfig::HOME_PAGE_TYPE]
        ];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }

        if (isset($params['pop_time_sdate']) && !empty($params['pop_time_sdate'])
            && isset($params['pop_time_edate']) && !empty($params['pop_time_edate'])) {
            $conditions[] = ['start_time', '>=', strtotime($params['pop_time_sdate'])];
            $conditions[] = ['end_time', '<=', strtotime($params['pop_time_edate']) + 86400];
        }

        if (isset($params['effective_status']) && !empty($params['effective_status'])) {
            $time = time();
            if ($params['effective_status'] == 1) {
                $conditions[] = ['start_time', '>', $time];
            } else if ($params['effective_status'] == 2) {
                $conditions[] = ['start_time', '<', $time];
                $conditions[] = ['end_time', '>', $time];
            } else if ($params['effective_status'] == 3) {
                $conditions[] = ['end_time', '<', $time];
            }
        }

        return $conditions;
    }

}