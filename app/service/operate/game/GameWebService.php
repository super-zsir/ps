<?php

namespace Imee\Service\Operate\Game;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Rpc\GameRpcService;
use Imee\Service\StatusService;

class GameWebService
{
    const STATE_OFF = 0;
    const STATE_ON = 1;

    // 重置方式  日、周、月、不重置
    const RESET_TYPE_DAY = 1;
    const RESET_TYPE_WEEK = 2;
    const RESET_TYPE_MONTH = 3;
    const RESET_TYPE_NO = 4;

    /**
     * @var GameRpcService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new GameRpcService();
    }

    public function getList(array $params): array
    {
        list($res, $msg, $data) = $this->rpcService->getGameConfigList($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        foreach ($data as &$item) {
            $item['areasID'] = array_map('strval', $item['areasID'] ?? []);
            $item['bigarea_name'] = XsBigarea::formatBigAreaName($item['areasID'], ',', '<br />');
            $item['bigarea_id_str'] = implode('<br />', $item['areasID']);
            $item['isOpen'] = strval($item['isOpen'] ?? 0);
            $item['model_id'] = $item['poolID'];
            $item['resetType'] = strval($item['resetType'] ?? 4);
        }

        return $data;
    }

    public function modify(array $params): array
    {
        $this->verify($params);
        // 详情
        list($res, $msg, $info) = $this->rpcService->getConfigDetail($params['poolID']);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $data = [
            'poolID'          => (int)$params['poolID'],
            'areasID'         => array_map('intval', $params['areasID'] ?? $info['areasID']),
            'injectRate'      => strval($params['injectRate'] ?? $info['injectRate']),
            'resetType'       => (int)($params['resetType'] ?? $info['resetType']),
            'resetParam'      => strval($params['resetParam'] ?? ($info['resetParam'] ?? '')),
            'resetTime'       => strval($params['resetTime'] ?? ($info['resetTime'] ?? '')),
            'resetRatio'      => strval($params['resetRatio'] ?? ($info['resetRatio'] ?? '')),
            'resetFixedValue' => intval($params['resetFixedValue'] ?? ($info['resetFixedValue'] ?? 0)),
            'isOpen'          => intval($params['isOpen'] ?? ($info['isOpen'] ?? self::STATE_OFF))
        ];

        // 修改
        list($res, $msg) = $this->rpcService->updateConfigData($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return [
            'poolID' => $data['poolID'],
            'before_json' => $info,
            'after_json' => $data,
        ];
    }

    private function verify(array $params)
    {
        if (!isset($params['poolID']) || $params['poolID'] < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '奖池ID错误');
        }

        // 修改时检验
        if (isset($params['areasID']) && in_array(99, $params['areasID']) && count($params['areasID']) > 1) {
            throw new ApiException(ApiException::MSG_ERROR, '选择全区后不需要在选其他子区');
        }

        // 最大重置参数区间值（默认为7）
        // 周【1-7】 月【1-28】
        $maxResetRange = 7;
        if (isset($params['resetType']) && $params['resetType'] == self::RESET_TYPE_MONTH) {
            $maxResetRange = 28;
        }

        if (isset($params['resetParam']) && ($params['resetParam'] < 1 || $params['resetParam'] > $maxResetRange)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前重置参数区间错误，周【1-7】,月【1-28】');
        }

        if (isset($params['isOpen']) && !in_array($params['isOpen'], [self::STATE_ON, self::STATE_OFF])) {
            throw new ApiException(ApiException::MSG_ERROR, '启用状态错误');
        }
    }

    public static function getBigAreaMap()
    {
        $bigArea = XsBigarea::getAllNewBigArea();
        $bigArea[99] = '全区';

        return StatusService::formatMap($bigArea, 'label,value');
    }
}