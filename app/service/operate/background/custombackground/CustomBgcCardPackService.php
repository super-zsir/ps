<?php

namespace Imee\Service\Operate\Background\Custombackground;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class CustomBgcCardPackService
{
    public function getList(array $params): array
    {
        $cond = $this->getConditions($params);
        $list = (new PsService())->customBgcCardpackList($cond);
        if (empty($list['data'])) {
            return [];
        }
        $uids = array_column($list['data'], 'uid');
        $userInfo = XsUserProfile::getBatchCommon($uids, ['uid', 'name'], 'uid');
        foreach ($list['data'] as &$v) {
            $v['num'] = 1;
            $v['nickname'] = $userInfo[$v['uid']]['name'] ?? '';
            $v['valid_term'] = (int) ($v['valid_term'] / 86400);
            $v['big_area_id'] = (string)$v['bigarea_id'];
            $v['get_at'] = empty($v['get_at']) ? '' : Helper::now($v['get_at']);
            $v['expired_at'] = empty($v['expired_at']) ? '' : Helper::now($v['expired_at']);
        }
        return $list;
    }

    public function delete(int $id)
    {
        [$res, $msg] = (new PsService())->customBgcCardpackDel($id);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getConditions(array $params): array
    {
        $conditions = [
            'page_num' => $params['page'],
            'page_size' => $params['limit']
        ];
        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions['uid'] = $params['uid'];
        }
        if (isset($params['big_area_id']) && !empty($params['big_area_id'])) {
            $conditions['big_area_id'] = $params['big_area_id'];
        }

        if (isset($params['card_type']) && is_numeric($params['card_type'])) {
            $conditions['card_type'] = intval($params['card_type']);
        } else {
            $conditions['card_type'] = -1;
        }

        return array_map('intval', $conditions);
    }
}