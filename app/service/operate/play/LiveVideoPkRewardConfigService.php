<?php

namespace Imee\Service\Operate\Play;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsEmoticonsGroup;
use Imee\Models\Xs\XsEmoticonsMeta;
use Imee\Models\Xs\XsMultiAnchorVideoLivePkAwardConfig;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Service\Helper;
use Imee\Service\Operate\Emoticons\EmoticonsMaterialService;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class LiveVideoPkRewardConfigService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getListAndTotal(): array
    {
        list($res, $msg, $data) = $this->rpcService->queryMultiAnchorAwardConfigList();
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($data as &$item) {
            $item['num'] = count($item['config_list']);
            $item['dateline'] = Helper::now($item['dateline']);
            $item['task_info'] = $this->formatTaskList($item['config_list']);
        }

        return ['data' => $data, 'total' => count($data)];
    }

    private function formatTaskList(array $configList): string
    {
        $str = '';
        foreach ($configList as $k => $item) {
            $str .= sprintf('<p>任务%d：</p>', $k+1);
            $str .= sprintf('<p>pk任务值：%s</p>', $item['task_value']);
            $str .= sprintf('<p>奖励物品：%s</p>', $this->formatConfigList($item['config_list']));
        }
        return $str;
    }

    /**
     * 格式话configList数据
     * @param array $configList
     * @return string
     */
    private function formatConfigList(array $configList): string
    {
        $newConfigList = [];
        foreach ($configList as $config) {
            $type = $config['type'];
            $id = $config['id'];
            $rewardType = $rewardName = '';
            switch ($type) {
                case XsMultiAnchorVideoLivePkAwardConfig::TYPE_COMMODITY:
                    $rewardInfo = XsCommodityAdmin::getInfo($id);
                    if ($rewardInfo) {
                        $rewardType = $rewardInfo['type'];
                        $rewardName = $rewardInfo['name'];
                    }
                    break;
                case XsMultiAnchorVideoLivePkAwardConfig::TYPE_ROOM_BACKGROUND:
                    $rewardInfo = XsChatroomBackgroundMall::getInfo($id);
                    $rewardType = $type;
                    $rewardName = $rewardInfo['name'] ?? '';
                    break;
                case XsMultiAnchorVideoLivePkAwardConfig::COMMODITY_TYPE_PK:
                    $propCard = XsPropCard::findOne($id);
                    $rewardType = $type;
                    $rewardName = '';
                    if (!empty($propCard['prop_card_config_id'])) {
                        $propCardConfig = XsPropCardConfig::findOne($propCard['prop_card_config_id']);
                        $name = @json_decode($propCardConfig['name_json'] ?? '', true);
                        if (isset($name['cn'])) {
                            $rewardName = $name['cn'];
                        }
                    }
                    break;
                case XsMultiAnchorVideoLivePkAwardConfig::TYPE_EMOTICON:
                    $rewardType = $type;
                    $emoticon = XsEmoticons::findOne($id);
                    $rewardName = '';
                    if (!empty($emoticon['group_id'])) {
                        $group = XsEmoticonsGroup::findOne($emoticon['group_id']);
                        $rewardName = "【ID:{$group['id']}】{$group['name']}";
                    }
                    break;
            }
            $newConfigList[] = str_replace(["{type}", "{id}", "{name}"], [XsMultiAnchorVideoLivePkAwardConfig::$typeMap[$rewardType], $id, $rewardName], '{type}{id:{id}}{name}');
        }

        return implode('，', $newConfigList);
    }

    /**
     * 配置奖励
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function config(array $params): array
    {
        $data = $this->validateConfig($params)
            ->formatConfig($params);
        list($res, $msg, $data) = $this->rpcService->updateMultiAnchorAwardConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['big_area_id' => $params['big_area_id'], 'after_json' => $data];
    }

    /**
     * 配置详情
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function info(array $params): array
    {
        $bigAreaId = intval($params['big_area_id'] ?? 0);
        if (empty($bigAreaId)) {
            throw new ApiException(ApiException::MSG_ERROR, '大区错误');
        }

        list($res, $msg, $data) = $this->rpcService->getMultiAnchorAwardConfig($bigAreaId);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $data['big_area_id'] = (string)$data['big_area_id'];

        $configArr = [];
        $config = $data['config_list'];

        foreach ($config as $item) {
            foreach ($item['config_list'] as &$config) {
                $config['type'] = (string)$config['type'];
                $config['id'] = (string)$config['id'];

                if ($config['type'] == XsMultiAnchorVideoLivePkAwardConfig::COMMODITY_TYPE_PK) {
                    $config['num_day'] = $config['num'] ?? 1;
                }

                // 物品类型需要转换成子类型
                if ($config['type'] == XsMultiAnchorVideoLivePkAwardConfig::TYPE_COMMODITY) {
                    $commodityInfo = XsCommodityAdmin::getInfo($config['id']);
                    $commodityInfo && $config['type'] = $commodityInfo['type'];
                }
            }

            $configArr[] = $item;
        }
        $data['config'] = $configArr;

        return $data;
    }

    /**
     * 格式化配置数据
     * @param array $params
     * @return array
     */
    private function formatConfig(array $params): array
    {
        $config = $params['config'];

        $configArr = [];
        foreach ($config as $item) {
            $taskValue = (int)$item['task_value'];
            $configList = $item['config_list'];

            $rewardConfig = [];
            foreach ($configList as $configItem) {
                $type = $configItem['type'];
                $id = (int)$configItem['id'];
                $weight = (int)$configItem['weight'];
                $num = (int)($configItem['num'] ?? 1);

                // 物品子类型需要转换一下类型
                if (in_array($type, XsMultiAnchorVideoLivePkAwardConfig::$commodityTypeArr)) {
                    $type = XsMultiAnchorVideoLivePkAwardConfig::TYPE_COMMODITY;
                }

                if ($type == XsMultiAnchorVideoLivePkAwardConfig::COMMODITY_TYPE_PK) {
                    $num = (int)($configItem['num_day'] ?? 1);
                }

                $rewardConfig[] = [
                    'type'   => (int)$type,
                    'id'     => $id,
                    'weight' => $weight,
                    'num'    => $num,
                ];
            }

            $configArr[] = [
                'task_value' => $taskValue,
                'config_list' => $rewardConfig,
            ];
        }

        $bigAreaId = (int)$params['big_area_id'];

        $topN = (int)$params['top_n'];
        $operator = Helper::getAdminName($params['admin_uid']);

        return [
            'big_area_id' => $bigAreaId,
            'top_n'       => $topN,
            'config_list' => $configArr,
            'operator'    => $operator,
            'dateline'    => time()
        ];
    }

    /**
     * 验证奖励配置
     * @param array $params
     * @return $this
     * @throws ApiException
     */
    private function validateConfig(array $params)
    {
        foreach ($params['config'] as $item) {
            $configList = $item['config_list'];
            $sumWeight = array_sum(array_column($configList, 'weight'));

            if ($sumWeight != 100) {
                throw new ApiException(ApiException::MSG_ERROR, '配置项下 奖励概率加起来需等于100');
            }
        }


        return $this;
    }

    /**
     * 服务枚举
     * @return array
     */
    public function getOptions(): array
    {
        $statusService = new StatusService();
        $bigArea = $statusService->getFamilyBigArea(null, 'label,value');
        $rewardType = StatusService::formatMap(XsMultiAnchorVideoLivePkAwardConfig::$typeMap, 'label,value');
        $commodityList = XsCommodityAdmin::getCommodityListByTypes(XsMultiAnchorVideoLivePkAwardConfig::$commodityTypeArr);
        $rewardOptions = [];
        foreach ($commodityList as $commodity) {
            $format = [
                'label' => $commodity['ocid'] . '-' . $commodity['name'],
                'value' => (string)$commodity['ocid']
            ];
            if (isset($rewardOptions[$commodity['type']])) {
                $rewardOptions[$commodity['type']][] = $format;
            } else {
                $rewardOptions[$commodity['type']] = [$format];
            }
        }
        $rewardOptions[(string)XsMultiAnchorVideoLivePkAwardConfig::TYPE_ROOM_BACKGROUND] = $statusService->getBackgroundMap(null, 'label,value');

        $rewardOptions[(string)XsMultiAnchorVideoLivePkAwardConfig::COMMODITY_TYPE_PK] = StatusService::formatMap(XsPropCard::getPkPropCardOptions());
        $rewardOptions[(string)XsMultiAnchorVideoLivePkAwardConfig::TYPE_EMOTICON] = StatusService::formatMap(XsEmoticons::getPayOption());

        return [
            'bigArea'       => $bigArea,
            'rewardType'    => $rewardType,
            'rewardOptions' => $rewardOptions,
        ];
    }
}