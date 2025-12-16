<?php

namespace Imee\Service\Operate\Roombackground;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class BackgroundKnapsackService
{
    /**
     * @var PsService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $list = $this->rpcService->userChatroomBackgroundList($params);
        foreach ($list['data'] as &$item) {
            $item['duration'] = $this->formatDuration($item['duration']);
            $item['get_time']   = !empty($item['get_time']) ? Helper::now($item['get_time']) : '';
            $item['expired_at'] = !empty($item['expired_at']) ? Helper::now($item['expired_at']) : '';
        }
        return $list;
    }

    private function formatDuration($duration)
    {
        if (empty($duration)) {
            return 0;
        }

        return ceil(((int) $duration) / 86400);
    }

    public function modify(array $params): array
    {
        $data = [
            'uid' => (int) $params['uid'],
            'bg_id' => (int) $params['bg_id'],
            'duration' => ((int) $params['duration']) * 86400,
        ];

        if ($params['where'] == 2) {
            $data['duration'] = $data['duration'] * -1;
        }

        list($res, $msg) = $this->rpcService->editUserRoomBackground($data);

        if (!$res) {
            return [false, $msg];
        }
        $this->addLog($params['id'], $data, BmsOperateLog::ACTION_UPDATE);

        return [true, ''];
    }

    public function addLog($id,  $afterJson, $action, $beforeJson = '', $content = '')
    {
        $data = [
            'model_id'     => $id,
            'model'        => 'background_knapsack',
            'action'       => $action,
            'content'      => $content,
            'before_json'  => $beforeJson,
            'after_json'   => $afterJson,
            'operate_id'   => Helper::getSystemUid(),
        ];

        OperateLog::addOperateLog($data);
    }
}