<?php

namespace Imee\Service\Operate\Play\Luckyfruit;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateHistory;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstLuckyFruitWeightTab;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class WeightService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(int $tabId): array
    {
        list($res, $data) = $this->rpcService->getLuckyFruitsWeight($tabId);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }
        $logs = BmsOperateLog::getFirstLogList('luckyfruitweight', array_column($data, 'id'));

        foreach ($data as &$item) {
            $item['operator'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
        }
        return $data;
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $weight = intval($params['weight'] ?? -1);

        if (empty($id) || $weight < 0) {
            throw new ApiException(ApiException::MSG_ERROR, 'Params Error');
        }
        $data = [
            'id'     => $id,
            'weight' => $weight
        ];

        list($res, $msg) = $this->rpcService->editLuckyFruitsWeight(['weight_list' => $data]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, ['after_json' => $data]];
    }

    public function getTabList(): array
    {
        return XsstLuckyFruitWeightTab::findAll();
    }

    public function createTab(): array
    {
        list($res, $msg) = $this->rpcService->initLuckyFruitsWeight();
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $data = [
            'tab_id' => $msg,
            'name'   => '权重配置' . $msg
        ];

        XsstLuckyFruitWeightTab::add($data);

        return ['tag_id' => $msg, 'after_json' => $data];
    }

    public function modifyTab(array $params): array
    {
        $tabId = $params['tab_id'] ?? 0;
        $name = $params['name'] ?? '';

        if (empty($tabId) || empty($name)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Params Error');
        }

        $tab = XsstLuckyFruitWeightTab::findOneByWhere([
            ['tab_id', '=', $tabId]
        ]);
        if (empty($tab)) {
            throw new ApiException(ApiException::MSG_ERROR, 'tab配置不存在');
        }

        list($res, $msg) = XsstLuckyFruitWeightTab::updateByWhere([
            ['tab_id', '=', $tabId]
        ], [
            'name' => $name,
            'update_uid' => Helper::getSystemUid(),
            'dateline' => time()
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '修改失败, 失败原因：' . $msg);
        }

        return ['tag_id' => $tabId, 'before_json'=> ['name' => $tab['name']], 'after_json' => ['name' => $name]];
    }

    public function deleteTab(int $tabId): void
    {
        list($res, $msg) = $this->rpcService->deleteLuckyFruitsWeight($tabId);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        XsstLuckyFruitWeightTab::deleteByWhere([
            ['tab_id', '=', $tabId]
        ]);
    }

    public function modifyTotal($params): array
    {
        $c = array_get($params, 'c');
        if ($c == 'info') {
            $tabId = array_get($params, 'tab_id', 0);
            list($res, $data) = $this->rpcService->getLuckyFruitsWeight($tabId);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $data);
            }
            return $data;
        } else {

            $operateId = array_get($params, 'admin_id', 0);
            $operateName = Helper::getAdminName($operateId ?? '');

            $data = array_get($params, 'data', []);
            if (!is_array($data) || empty($data)) {
                throw new ApiException(ApiException::MSG_ERROR, 'Params Error');
            }

            $send = [];
            $logData = [];
            foreach ($data as $v) {
                $id = array_get($v, 'id', 0);
                $weight = array_get($v, 'weight', -1);
                if (empty($id) || $weight < 0) {
                    throw new ApiException(ApiException::MSG_ERROR, 'Params Error');
                }
                $send[] = [
                    'id' => intval($id),
                    'weight' => intval($weight),
                ];
                $logData[] = [
                    'content' => '编辑',
                    'after_json' => ['weight' => $weight],
                    'type' => BmsOperateLog::TYPE_OPERATE_LOG,
                    'model' => 'luckyfruitweight',
                    'model_id' => $id,
                    'action' => BmsOperateLog::ACTION_UPDATE,
                    'operate_id' => $operateId,
                    'operate_name' => $operateName,
                ];
            }

            list($res, $msg) = $this->rpcService->editLuckyFruitsWeight(['weight_list' => $send]);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }

            foreach ($logData as $log) {
                OperateLog::addOperateLog($log);
            }
            return $send;
        }

    }
}