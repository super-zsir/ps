<?php

namespace Imee\Service\Game;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class GameLimitService
{
    public function getList(): array
    {
        $list = (new PsService())->getProbabilityGameSwitch();
        foreach ($list['data'] as &$item) {
            $item['game'] = XsGlobalConfig::$gameCenterIdMap[$item['game_center_id']] ?? '';
            $item['first_recharge_limit'] = $item['first_recharge_limit'] ? '1' : '0';
        }

        return $list;
    }

    public function modify(array $params)
    {
        [$res, $msg] = (new PsService())->editProbabilityGameSwitch($params);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

    }
}