<?php

namespace Imee\Service\Operate\Livesticker;


use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsRoomSpecialEffectsConfig;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class CustomStickerResourceService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $list = $this->rpcService->getCustomStickerResourceList($this->getConditions($params));
        foreach ($list['data'] as &$item) {
            $item['id'] = $item['custom_sticker']['id'];
            $item['name'] = $item['custom_sticker']['name'];
            $item['remark'] = $item['custom_sticker']['remark'];
            $item['model_id'] = $item['custom_sticker']['id'];
            $item['img'] = $item['custom_sticker']['img'];
            $item['mirror_img'] = $item['custom_sticker']['mirror_img'];
            $item['img_all'] = Helper::getHeadUrl($item['custom_sticker']['img']);
            $item['mirror_img_all'] = Helper::getHeadUrl($item['custom_sticker']['mirror_img']);
            $item['use_area'] = $this->getAreaName($item['using_big_area_list']);
            unset($item['custom_sticker']);
            unset($item['using_big_area_list']);
        }

        return $list;
    }

    private function getAreaName($bigAreas)
    {
        if (empty($bigAreas)) {
            return '-';
        }
        $bigAreaList = XsBigarea::getAllNewBigArea();
        $useArea = [];
        foreach ($bigAreas as $area) {
            if (isset($bigAreaList[$area])) {
                array_push($useArea, $bigAreaList[$area]);
            }
        }

        return implode(',', $useArea);
    }

    public function add(array $params)
    {
        $data = [
            'name'       => $params['name'],
            'img'        => $params['img'],
            'mirror_img' => $params['mirror_img'],
            'remark'     => $params['remark'] ?? ''
        ];

        if ($data['remark'] && strlen($data['remark']) >= 768) {
            throw new ApiException(ApiException::MSG_ERROR, '备注不能过长');
        }
        [$res, $msg, $id] = $this->rpcService->customStickerResourceAdd($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function edit(array $params)
    {
        $info = XsRoomSpecialEffectsConfig::findOne($params['id']);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '素材不存在');
        }
        $data = [
            'id'         => (int)$params['id'],
            'name'       => $params['name'],
            'img'        => $params['img'],
            'mirror_img' => $params['mirror_img'],
            'remark'     => $params['remark'] ?? ''
        ];

        if ($data['remark'] && (strlen($data['remark']) >= 768)) {
            throw new ApiException(ApiException::MSG_ERROR, '备注不能过长');
        }

        [$res, $msg] = $this->rpcService->customStickerResourceEdit($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $afterJson = [
            'name'       => $params['name'],
            'img'        => $info['img'],
            'mirror_img' => $info['mirror_img'],
            'remark'     => $params['remark'] ?? '',
        ];

        $beforeJson = [
            'name'       => $info['name'],
            'img'        => $info['img'],
            'mirror_img' => $info['mirror_img'],
            'remark'     => $info['remark'],
        ];

        return ['id' => $params['id'], 'after_json' => $afterJson, 'before_json' => $beforeJson];
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            'page' => [
                'page_index' => (int)($params['page'] ?? 1),
                'page_size'  => (int)($params['limit'] ?? 15)
            ],
        ];
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions['sticker_id'] = (int)$params['id'];
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $conditions['name'] = $params['name'];
        }

        return $conditions;
    }
}