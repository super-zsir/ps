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

class RoomSkinConfigService
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
        $list = XsRoomSkin::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('roomskinconfig', $ids);
        foreach ($list['data'] as &$item) {
            $micSkin = json_decode($item['extend_info'], true);
            $item['cover']           = Helper::getHeadUrl($item['cover']);
            $item['img']             = Helper::getHeadUrl($item['img']);
            $item['title_left_img']  = Helper::getHeadUrl($micSkin['title_left_img'] ?? '');
            $item['title_right_img'] = Helper::getHeadUrl($micSkin['title_right_img'] ?? '');
            $item['no_mic_img']      = Helper::getHeadUrl($micSkin['no_mic_img'] ?? '');
            $item['lock_mic_img']    = Helper::getHeadUrl($micSkin['lock_mic_img'] ?? '');
            $item['apply_mic_img']   = Helper::getHeadUrl($micSkin['apply_mic_img'] ?? '');
            if ($item['type'] == XsRoomSkin::TYPE_SKIN) {
                $item['frame_color'] = $micSkin['frame_color'] ?? '';
            } else if ($item['type'] == XsRoomSkin::TYPE_TITLE) {
                $item['title_frame_color'] = $micSkin['frame_color'] ?? '';
            }
            $item['operator']        = $logs[$item['id']]['operate_name'] ?? '-';
        }

        return $list;
    }

    public function add(array $params)
    {
        $data = [
            'type'            => (int)$params['type'],
            'name'            => $params['name'],
            'cover'           => $params['cover'],
            'img'             => '',
            'title_left_img'  => '',
            'title_right_img' => '',
            'no_mic_img'      => '',
            'lock_mic_img'    => '',
            'apply_mic_img'   => '',
            'frame_color'     => '',
            'status'          => (int)$params['status'],
        ];
        // 验证数据
        if ($data['type'] == XsRoomSkin::TYPE_ROOM) {
            $data['img'] = $params['img'] ?? '';
        } else if ($data['type'] == XsRoomSkin::TYPE_SKIN) {
            $data['frame_color']   = $params['frame_color'] ?? '';
            $data['no_mic_img']    = $params['no_mic_img'] ?? '';
            $data['lock_mic_img']  = $params['lock_mic_img'] ?? '';
            $data['apply_mic_img'] = $params['apply_mic_img'] ?? '';
        } else if ($data['type'] == XsRoomSkin::TYPE_TITLE) {
            $data['frame_color']     = $params['frame_color'] ?? '';
            $data['title_left_img']  = $params['title_left_img'] ?? '';
            $data['title_right_img'] = $params['title_right_img'] ?? '';
        }
        return $this->rpcService->createRoomSkin($data);
    }

    private function checkFrameColor($frameColor)
    {
        if (empty($frameColor)) {
            return '';
        }

        if (!preg_match('/^#[a-f0-9]{6}$/i', $frameColor)) {
            throw new ApiException(ApiException::MSG_ERROR, '颜色格式错误');
        }

        return $frameColor;
    }

    public function delete(int $id)
    {
        $res = XsUserRoomSkin::checkIsExpire($id);
        if ($res) {
            return [false, '当前皮肤已被下发使用，不能进行删除'];
        }

        return $this->rpcService->delRoomSkin($id);
    }

    public function getConditions(array $params)
    {
        $conditions = [];

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }

        if (isset($params['type']) && !empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        }

        return $conditions;
    }
}