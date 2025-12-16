<?php

namespace Imee\Service\Operate\Play\Greedybox;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsTarotOddsPercent;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class OddsService
{
    private $gameId;
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct(int $gameId)
    {
        $this->rpcService = new PsService();
        $this->gameId = $gameId;
    }

    public function getList(): array
    {
        list($res, $data) = $this->rpcService->queryGameItemOddsList(['game_id' => $this->gameId]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }
        foreach ($data as &$item) {
            $item['percent'] = $item['percent'] / 100;
            $item['percent_rate'] = $item['percent'] . '%';
        }

        return $data;
    }

    public function modify(array $params): array
    {
        $data = $this->verify($params);
        list($res, $msg) = $this->rpcService->editGameItemOdds($data, $this->gameId);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['item_id' => Helper::arrayFilter($params['item_list'], 'item_id'), 'after_json' => json_encode($data)];
    }

    private function verify(array $params): array
    {
        $list = $params['item_list'] ?? [];
        if (empty($list)) {
            throw new ApiException(ApiException::MSG_ERROR, '修改内容不能为空');
        }

        $update = [];
        foreach ($list as $item) {
            if ($item['percent'] < 0 || $item['percent'] > 100) {
                throw new ApiException(ApiException::MSG_ERROR, '概率值区间为0-100');
            }
            $update[] = [
                'item_id' => $item['item_id'],
                'odds'    => $item['odds'],
                'percent' => intval($item['percent'] * 100),
            ];
        }

        return $update;
    }
}