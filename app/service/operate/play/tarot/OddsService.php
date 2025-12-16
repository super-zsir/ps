<?php

namespace Imee\Service\Operate\Play\Tarot;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsTarotOddsPercent;
use Imee\Service\Rpc\PsService;

class OddsService
{
    private $prefix = 'percent_';
    private $suffix = '_id';

    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(): array
    {
        list($res, $data) = $this->rpcService->tarotOddsList();
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }
        $index = 1;
        foreach ($data as &$item) {
            $item['index'] = $index;
            $tarotOddsList = array_column($item['tarot_odds_list'], null, 'type');
            $tmp = [];
            foreach (XsTarotOddsPercent::$typeMap as $key => $type) {
                $tarotOdds = $tarotOddsList[$key] ?? [];
                if ($tarotOdds) {
                    $tmp[$this->prefix . $type] = $tarotOdds['percent'] / 100;
                    $tmp[$this->prefix . $type . '_rate'] = ($tarotOdds['percent'] / 100) . '%';
                    $tmp[$this->prefix . $type . $this->suffix] = $tarotOdds['id'];
                }
            }
            $index++;
            $item = array_merge($item, $tmp);
        }

        return $data;
    }

    public function modify(array $params): array
    {
        $data = $this->verify($params);
        list($res, $msg) = $this->rpcService->editTarotOdds($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['after_json' => json_encode($data)];
    }

    private function verify(array $params): array
    {
        $data = [];
        $typeMap = array_flip(XsTarotOddsPercent::$typeMap);
        foreach ($params as $key => $item) {
            if (strpos($key, $this->prefix) === false) {
                continue;
            }

            if ($item < 0 || $item > 100) {
                throw new ApiException(ApiException::MSG_ERROR, '预期值区间为0-100');
            }
            $tmp = explode('_', $key);
            $data[] = [
                'id'      => $tmp[2],
                'type'    => $typeMap[$tmp[1]],
                'percent' => intval($item * 100)
            ];
        }

        return $data;
    }
}