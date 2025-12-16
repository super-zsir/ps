<?php

namespace Imee\Service\Operate\Linkjump;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsLinkWhiteList;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;

class OutLinkJumpWhitelistService
{
    public function getList(array $params): array
    {
        $conditions = [];
        if (isset($params['link_url']) && !empty($params['link_url'])) {
            $conditions[] = ['link_url', 'like', "%{$params['link_url']}%"];
        }
        $list = XsLinkWhiteList::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $logs = BmsOperateLog::getFirstLogList('outlinkjumpwhitelist', array_column($list['data'], 'id'));
        foreach ($list['data'] as &$v) {
            $v['link_url'] = [
                'title' => $v['link_url'],
                'value' => $v['link_url'],
                'type' => 'url',
                'resourceType' => 'static',
                'url'  => $v['link_url'],
            ];
            $v['admin'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $list;
    }

    public function create(array $params): array
    {
        $link = $params['link_url'] ?? '';
        if (empty($link)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Params Error');
        }

        list($res, $id) = XsLinkWhiteList::add([
            'link_url' => $link
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '数据添加失败，失败原因：' . $id);
        }

        return ['id' => $id, 'after_json' => ['link_url' => $link]];
    }

    public function delete(int $id): void
    {
        XsLinkWhiteList::deleteById($id);
    }
}
