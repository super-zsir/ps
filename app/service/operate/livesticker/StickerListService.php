<?php

namespace Imee\Service\Operate\Livesticker;


use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsRoomSpecialEffectsConfig;
use Imee\Models\Xs\XsRoomSpecialEffectsManage;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class StickerListService
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
        $list = $this->rpcService->getStickerManageList($params);
        foreach ($list['data'] as &$item) {
            $item['model_id']     = $item['manage_id'];
            $item['sticker_id']   = (string) $item['sticker']['id'];
            $item['sticker_name'] = $item['sticker']['name'];
            $item['sticker_img']  = Helper::getHeadUrl($item['sticker']['img']);
        }
        return $list;
    }

    public function add(array $params)
    {
        $data = [
            'sn'      => (int)$params['sn'],
            'sticker' => [
                'id' => (int)$params['sticker_id']
            ],
            'status'  => (int)$params['status']
        ];

        // 选择全部大区是分成多个请求 0为全部大区
        if ($params['big_area_id'] == 0) {
            $bigAreaList = XsBigarea::findAll();
            foreach ($bigAreaList as $area) {
                $params = array_merge($data, ['big_area_id' => (int) $area['id']]);
                $this->callRpcAdd($params);
                usleep(1000);
            }
        } else {
            $data['big_area_id'] = (int) $params['big_area_id'];
            $this->callRpcAdd($data);
        }

    }

    private function callRpcAdd($data)
    {
        [$res, $msg, $id] = $this->rpcService->stickerManageAdd($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $data['id'] = $id;
        $this->addLog($data);
    }

    public function edit(array $params)
    {
        $info = XsRoomSpecialEffectsManage::findOne($params['manage_id']);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '修改数据不存在');
        }
        $this->check($params);
        $data = [
            'manage_id'   => (int)$params['manage_id'],
            'big_area_id' => (int)$params['big_area_id'],
            'sn'          => (int)$params['sn'],
            'sticker' => [
                'id' => (int)$params['sticker_id']
            ],
            'status'      => (int)$params['status']
        ];

        [$res, $msg] = $this->rpcService->stickerManageEdit($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $afterJson = [
            'big_area_id' => $params['big_area_id'],
            'sticker_id'  => $params['sticker_id'],
            'sn'          => $params['sn'],
            'status'      => $params['status'],
        ];

        $beforeJson = [
            'big_area_id' => $info['big_area_id'],
            'sticker_id'  => $info['se_id'],
            'sn'          => $info['sn'],
            'status'      => $info['status'],
        ];

        return ['id' => $params['manage_id'], 'after_json' => $afterJson, 'before_json' => $beforeJson];
    }
    
    public function check($params)
    {
        $info = XsRoomSpecialEffectsManage::findOneByWhere([
            ['id', '<>', $params['manage_id']],
            ['se_id', '=', $params['sticker_id']],
            ['se_type', '=', XsRoomSpecialEffectsManage::STICKER_TYPE],
            ['big_area_id', '=', $params['big_area_id']],
        ]);
        if ($info) {
            throw new ApiException(ApiException::MSG_ERROR, '当前大区以设置此贴纸');
        }
        $res = XsRoomSpecialEffectsManage::findOneByWhere([
            ['se_type', '=', XsRoomSpecialEffectsManage::STICKER_TYPE],
            ['big_area_id', '=', $params['big_area_id']],
            ['se_id', '<>', $params['sticker_id']],
            ['sn', '=', $params['sn']]
        ]);
        if ($res) {
            throw new ApiException(ApiException::MSG_ERROR, '同个大区下不同贴纸的序号不能一致');
        }
    }



    private function addLog($data)
    {
        $afterJson = [
            'big_area_id' => $data['big_area_id'],
            'sticker_id'  => $data['sticker']['id'],
            'sn'          => $data['sn'],
            'status'      => $data['status'],
        ];

        OperateLog::addOperateLog([
            'before_json'  => '',
            'content'      => '新增',
            'after_json'   => $afterJson,
            'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
            'model'        => 'stickerlist',
            'model_id'     => $data['id'],
            'action'       => BmsOperateLog::ACTION_ADD,
            'operate_id'   => Helper::getSystemUid(),
            'operate_name' => Helper::getSystemUserInfo()['user_name'],
        ]);
    }
}