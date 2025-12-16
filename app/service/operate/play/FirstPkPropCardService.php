<?php

namespace Imee\Service\Operate\Play;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsPkPropCardFirstGiftConfig;
use Imee\Models\Xs\XsPropCard;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class FirstPkPropCardService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $bigareaId = intval($params['bigarea_id'] ?? 0);
        $query = [];
        $bigareaId && $query[] = ['id', '=', $bigareaId];

        $data = XsBigarea::getListAndTotal($query, 'id as bigarea_id', 'id asc', $page, $limit);

        $allConfig = XsPkPropCardFirstGiftConfig::getListByWhere([], '*', 'id desc');
        $allConfig = array_column($allConfig, null, 'bigarea_id');

        foreach ($data['data'] as &$rec) {
            $rec = array_merge($rec, $allConfig[$rec['bigarea_id']] ?? []);
            $dateline = array_get($rec, 'dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i', $dateline) : '';
            $rec['operator'] = Helper::getAdminName($rec['operator'] ?? '');
        }

        return $data;
    }

    /**
     * 配置奖励
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function config(array $params): array
    {
        $sendData = $this->validateConfig($params)
            ->formatConfig($params);
        list($res, $msg, $data) = $this->rpcService->updatePkPropCardFirstGiftConfig($sendData);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['bigarea_id' => $params['bigarea_id'], 'after_json' => $data];
    }

    /**
     * 配置详情
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function info(array $params): array
    {
        $bigAreaId = intval($params['bigarea_id'] ?? 0);
        if (empty($bigAreaId)) {
            throw new ApiException(ApiException::MSG_ERROR, '大区错误');
        }

        $data = XsPkPropCardFirstGiftConfig::findOneByWhere([['bigarea_id', '=', $bigAreaId]]);

        $data['bigarea_id'] = (string)$bigAreaId;
        $data['status'] = $data['status'] ?? XsPkPropCardFirstGiftConfig::STATUS_DELETED;

        $data['config_list'] = $data['config'] ? @json_decode($data['config'], true) : [];
        foreach ($data['config_list'] as &$config) {
            $config['type'] = (string)$config['type'];
            $config['id'] = (string)$config['id'];
        }

        return $data;
    }

    /**
     * 格式化配置数据
     * @param array $params
     * @return array
     */
    private function formatConfig(array $params): array
    {
        $bigAreaId = (int)$params['bigarea_id'];
        $diamond = (int)$params['diamond'];
        $status = (int)$params['status'];
        $operator = intval($params['admin_uid']);
        $configList = $params['config_list'] ?? [];
        $rewardConfig = [];
        foreach ($configList as $configItem) {
            $type = $configItem['type'];
            $id = intval($configItem['id'] ?? 0);
            $weight = intval($configItem['weight'] ?? 0);
            $validity = intval($configItem['validity'] ?? 0);

            if (isset(XsPkPropCardFirstGiftConfig::$typeMap[$type])) {
                $rewardConfig[] = [
                    'type'     => (int)$type,
                    'id'       => $id,
                    'weight'   => $weight,
                    'validity' => $validity,
                ];
            }
        }

        return [
            'bigarea_id' => $bigAreaId,
            'diamond'    => $diamond,
            'config'     => @json_encode($rewardConfig, JSON_UNESCAPED_UNICODE),
            'status'     => $status,
            'operator'   => $operator,
            'dateline'   => time()
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
//        $configList = $params['config_list'] ?? [];
//        $sumWeight = array_sum(array_column($configList, 'weight'));
//
//        if ($sumWeight != 100) {
//            throw new ApiException(ApiException::MSG_ERROR, '所有奖励概率加起来需等于100');
//        }

        $configList = $params['config_list'] ?? [];

        foreach ($configList as $configItem) {
            $type = $configItem['type'];
            $id = intval($configItem['id'] ?? 0);
            $weight = intval($configItem['weight'] ?? 0);
            $validity = intval($configItem['validity'] ?? 0);

            if ($type == XsPkPropCardFirstGiftConfig::COMMODITY_TYPE_PK) {
                if (empty($id)) {
                    throw new ApiException(ApiException::MSG_ERROR, '物品ID不能为0');
                }
                if (empty($weight)) {
                    throw new ApiException(ApiException::MSG_ERROR, '权重不能为0');
                }
                if (empty($validity)) {
                    throw new ApiException(ApiException::MSG_ERROR, '有效期不能为0');
                }
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
        $rewardType = StatusService::formatMap(XsPkPropCardFirstGiftConfig::$typeMap);
        $rewardOptions = [];
        $rewardOptions[(string)XsPkPropCardFirstGiftConfig::COMMODITY_TYPE_PK] = StatusService::formatMap(XsPropCard::getPkPropCardOptions());

        return [
            'bigArea'       => $bigArea,
            'rewardType'    => $rewardType,
            'rewardOptions' => $rewardOptions,
        ];
    }

}