<?php

namespace Imee\Service\Operate\Pop;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsDailyRecommendConfig;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

/**
 * 推荐位配置服务
 */
class PopRecommendService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    /**
     * 推荐位配置列表
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function getListAndTotal(array $params): array
    {
        $query = [
            'page'  => intval($params['page'] ?? 1),
            'limit' => intval($params['limit'] ?? 15),
        ];

        $bigAreaId = intval($params['bigarea_id'] ?? 0);
        $status = intval($params['status'] ?? 0);
        $recommendType = intval($params['recommend_type'] ?? 0);
        $startTime = trim($params['dateline_sdate'] ?? '');
        $endTime = trim($params['dateline_edate'] ?? '');

        $bigAreaId && $query['bigarea_id'] = $bigAreaId;
        $status && $query['status'] = $status;
        $recommendType && $query['recommend_type'] = $recommendType;
        $startTime && $query['start_time'] = strtotime($startTime);
        $endTime && $query['end_time'] = strtotime($endTime) + 86399;

        list($res, $msg, $list) = $this->rpcService->getRecommendConfigList($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($list['data'] as &$item) {
            $item['background_img'] && $item['background_img_all'] = Helper::getHeadUrl($item['background_img']);
            $item['start_time'] = Helper::now($item['start_time']);
            $item['end_time'] = Helper::now($item['end_time']);
            $item['update_time'] = Helper::now($item['update_time']);
            $item['recommend_rule'] = $item['recommend_rule'] ? explode(',', $item['recommend_rule']) : [];
            if ($item['jump_url']) {
                $item['jump_url'] = [
                    'title'        => $item['jump_url'],
                    'value'        => $item['jump_url'],
                    'type'         => 'url',
                    'url'          => $item['jump_url'],
                    'resourceType' => 'static'
                ];
            }
        }

        return $list;
    }

    /**
     * 推荐位配置创建
     * @param array $params
     * @return array
     */
    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $id) = $this->rpcService->recommendConfigCreate($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 推荐位配置编辑
     * @param array $params
     * @return array
     */
    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $config = XsDailyRecommendConfig::findOne($id);
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, '推荐位配置不存在');
        }
        $data = $this->formatData($params, true);
        list($res, $msg) = $this->rpcService->recommendConfigModify($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $config, 'after_json' => $data];
    }

    /**
     * 格式话请求数据
     * @param array $params
     * @param bool $isModify
     * @return array
     */
    private function formatData(array $params, bool $isModify = false): array
    {
        $id = intval($params['id'] ?? 0);
        $recommendType = intval($params['recommend_type']);
        $bigAreaId = intval($params['bigarea_id']);
        $startTime = strtotime($params['start_time']);
        $endTime = strtotime($params['end_time']);
        $backgroundImg = trim($params['background_img'] ?? '');
        $jumpUrl = trim($params['jump_url'] ?? '');
        $recommendRule = $params['recommend_rule'] ?? [];
        $operator = Helper::getAdminName($params['admin_id']);

        switch ($recommendType) {
            case XsDailyRecommendConfig::RECOMMEND_TYPE_UID:
                $jumpUrl = '';
                $recommendRule = implode(',', $recommendRule);
                break;
            case XsDailyRecommendConfig::RECOMMEND_TYPE_PAGES:
                $recommendRule = '';
                break;
        }

        $data = [
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'background_img' => $backgroundImg,
            'jump_url'       => $jumpUrl,
            'recommend_rule' => $recommendRule,
            'operator'       => $operator,
        ];

        if ($isModify) {
            $data['id'] = $id;
        } else {
            $data['recommend_type'] = $recommendType;
            $data['bigarea_id'] = $bigAreaId;
        }

        return $data;
    }

    /**
     * 推荐位配置删除
     * @param array $params
     * @return array
     */
    public function delete(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $config = XsDailyRecommendConfig::findOne($id);
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, '推荐位配置不存在');
        }

        list($res, $msg) = $this->rpcService->recommendConfigDelete(['id' => $id]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $config, 'after_json' => []];
    }

    /**
     * 推荐位配置详情
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function info(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        list($res, $data) = $this->rpcService->recommendConfigDetail(['id' => $id]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }

        $data['recommend_rule'] = $data['recommend_rule'] ? explode(',', $data['recommend_rule']) : [];
        $data['start_time'] = Helper::now($data['start_time']);
        $data['end_time'] = Helper::now($data['end_time']);
        $data['background_img'] && $data['background_img_all'] = Helper::getHeadUrl($data['background_img']);

        return $data;
    }

    /**
     * 推荐表单枚举
     * @return array
     */
    public function getOptions(): array
    {
        $statusService = new StatusService();
        return [
            'recommend_type' => $this->getRecommendTypeMaps(),
            'recommend_rule' => $this->getRecommendRuleMaps(),
            'bigarea_id'     => $statusService->getFamilyBigArea(null, 'label,value'),
        ];
    }

    /**
     * 推荐类型
     * @return array
     */
    public function getRecommendTypeMaps(): array
    {
        return StatusService::formatMap(XsDailyRecommendConfig::$recommendTypeMaps);
    }

    /**
     * 推荐规则
     * @return array
     */
    public function getRecommendRuleMaps(): array
    {
        return StatusService::formatMap(XsDailyRecommendConfig::$recommendRuleMaps);
    }

    /**
     * 推荐状态
     * @return array
     */
    public function getStatusMaps(): array
    {
        return StatusService::formatMap(XsDailyRecommendConfig::$statusMaps);
    }


}