<?php

namespace Imee\Service\Operate\Roomskin;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPopupsConfig;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsUserRoomSkin;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use Imee\Service\Rpc\PsService;

class RoomSkinSearchService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params)
    {
        $conditions = $this->getConditions($params);
        $list = XsUserRoomSkin::getListAndTotal($conditions, '*', 'expire_time desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        $time = time();
        foreach ($list['data'] as &$item) {
            $useTermDay = ceil(intval($item['expire_time'] - $time) / 86400);
            // 转换过期天数
            $item['use_term_day'] = $useTermDay > 0 ? $useTermDay : 0;
            $item['status'] = $item['expire_time'] > $time ? XsUserRoomSkin::NO_EXPIRE_STATUS : XsUserRoomSkin::EXPIRE_STATUS;
            $item['get_time'] = date('Y-m-d H:i', $item['get_time']);
            $item['expire_time'] = Helper::now($item['expire_time']);
        }

        return $list;
    }

    public function recovery(array $params)
    {
        $data = [
            'uid'      => (int)$params['uid'],
            'skin_id'  => (int)$params['skin_id'],
            'use_term' => ((int)$params['use_term_day']) * 86400,
        ];

        [$res, $msg] = $this->rpcService->recoveryUserRoomSkin($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getConditions(array $params)
    {
        $conditions = [];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }

        if (isset($params['skin_id']) && !empty($params['skin_id'])) {
            $conditions[] = ['skin_id', '=', $params['skin_id']];
        }
        // 筛选状态是否过期
        if (isset($params['status']) && !empty($params['status'])) {
            $time = time();
            if ($params['status'] == XsUserRoomSkin::EXPIRE_STATUS) {
                $conditions[] = ['expire_time', '<', $time];
            } else if ($params['status'] == XsUserRoomSkin::NO_EXPIRE_STATUS) {
                $conditions[] = ['expire_time', '>', $time];
            }
        }
        return $conditions;
    }
}