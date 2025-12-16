<?php

namespace Imee\Service\Operate\Play\Probability;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Game\GameLimitService;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LevelAreaService
{
    protected $models = [
        XsGlobalConfig::GAME_CENTER_ID_GREEDY       => 'greedylevel',
        XsGlobalConfig::GAME_CENTER_ID_SIC_BO       => 'sicbolevel',
        XsGlobalConfig::GAME_CENTER_ID_SLOT         => 'slotlevel',
        XsGlobalConfig::GAME_CENTER_ID_END          => 'dragontigerlevel',
        XsGlobalConfig::GAME_CENTER_ID_HORSE_RACE   => 'horseracelevel',
        XsGlobalConfig::GAME_CENTER_ID_LUCKY_FRUIT  => 'luckyfruitlevel',
        XsGlobalConfig::GAME_CENTER_ID_ROCKET_CRASH => 'crashlevel',
        XsGlobalConfig::GAME_CENTER_ID_TAROT        => 'tarotlevel',
        XsGlobalConfig::GAME_CENTER_ID_FISHING      => 'fishinglevel',
        XsGlobalConfig::GAME_CENTER_ID_SWEET_CANDY  => 'sweetcandylevel',
        XsGlobalConfig::GAME_CENTER_ID_GREEDY_BRUTAL => 'greedybrutallevel'
    ];

    public function getList(int $gameId)
    {
        $list = (new PsService())->getProbabilityGameBigAreaConfig($gameId);
        $data = XsBigarea::getListAndTotal([], 'id');
        $ids = array_column($data['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList($this->models[$gameId], $ids);
        foreach ($data['data'] as &$item) {
            $item['bigarea_id'] = $item['id'];
            $item['level'] = $list[$item['id']]['limit_level'] ?? 0;
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
            $item['admin_name'] = $logs[$item['id']]['operate_name'] ?? '-';
        }

        return $data;
    }

    /**
     * 获取首充开关状态
     * @param int $gameId
     * @return int[]
     */
    public function getFirstChargeSwitch(int $gameId): array
    {
        $list = (new GameLimitService())->getList();
        $list = array_column($list['data'], 'first_recharge_limit', 'game_center_id');

        return ['first_recharge_limit' => $list[$gameId] ?? '0'];
    }

    public function edit(array $params)
    {
        [$res, $msg] = (new PsService())->editProbabilityGameBigAreaConfig($params);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function modifySwitch(array $params)
    {
        (new GameLimitService())->modify($params);
    }
}