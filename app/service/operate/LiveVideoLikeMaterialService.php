<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsLikeIcon;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LiveVideoLikeMaterialService
{
    public function getList(array $params): array
    {
        $conditions = [];
        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ['name', 'like', "%{$params['name']}%"];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $now = time();
            switch ($params['status']) {
                case XsLikeIcon::WAIT_STATUS:
                    $conditions[] = ['start_at', '>', $now];
                    break;
                case XsLikeIcon::HAVE_STATUS:
                    $conditions[] = ['start_at', '<', $now];
                    $conditions[] = ['end_at', '>', $now];
                    break;
                case XsLikeIcon::END_STATUS:
                    $conditions[] = ['end_at', '<', $now];
                    break;
            }
        }
        $list = XsLikeIcon::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $logs = BmsOperateLog::getFirstLogList('livevideolikematerial', array_column($list['data'], 'id'));

        foreach ($list['data'] as &$item) {
            $item['icon_config'] = json_decode($item['icon_config'], true);
            $item['area_config'] = json_decode($item['area_config'], true);
            $item['status'] = $this->setStatus($item['start_at'], $item['end_at']);
            $item['area'] = XsBigarea::getAreaName($item['area_config']);
            $item['img'] = array_map(function ($v) {
                return Helper::getHeadUrl($v);
            }, $item['icon_config']);
            $item['admin'] = $logs[$item['id']]['operate_name'] ?? '';
            $item['end_at'] = $item['end_at'] == 4294967295 ? '永久' : Helper::now($item['end_at']);
            $item['start_end'] = Helper::now($item['start_at']) . '-' . $item['end_at'];
            $item['log'] = [
                'action' => 'modal',
                'title'  => '操作日志',
                'value'  => '操作日志',
                'type'   => 'guid',
                'guid'   => 'operatelog',
                'params' => [
                    ['model', 'livevideolikematerial'],
                    ['model_id', $item['id']]
                ]
            ];
        }

        return $list;
    }

    public function create(array $params): array
    {
        $data = [
            'name'        => $params['name'],
            'area_config' => array_map('intval', $params['area_config']),
            'icon_config' => array_column($params['material'], 'img'),
            'start_at'    => strtotime($params['start_at']),
        ];

        $endTime = 4294967295;
        if (isset($params['end_at']) && !empty($params['end_at'])) {
            $endTime = strtotime($params['end_at']);
            if ($endTime < $data['start_at']) {
                throw new ApiException(ApiException::MSG_ERROR, '结束时间不能小于开始时间');
            }
        }

        $data['end_at'] = $endTime;

        list($res, $msg) = (new PsService())->createLikeIcon($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $data['icon_config'] = json_encode($data['icon_config'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return ['id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $info = XsLikeIcon::findOne($params['id']);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前编辑配置不存在');
        }
        $data = [
            'id'          => $params['id'],
            'name'        => $params['name'],
            'area_config' => array_map('intval', $params['area_config']),
            'icon_config' => array_column($params['material'], 'img'),
            'start_at'    => strtotime($params['start_at']),
        ];

        $endTime = 4294967295;
        if (isset($params['end_at']) && !empty($params['end_at'])) {
            $endTime = strtotime($params['end_at']);
            if ($endTime < $data['start_at']) {
                throw new ApiException(ApiException::MSG_ERROR, '结束时间不能小于开始时间');
            }
        }
        $data['end_at'] = $endTime;

        list($res, $msg) = (new PsService())->editLikeIcon($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $data['icon_config'] = json_encode($data['icon_config'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return ['id' => $params['id'], 'before_json' => $info, 'after_json' => $data];
    }

    public function failure(int $id): array
    {
        $info = XsLikeIcon::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前编辑配置不存在');
        }

        $endTime = time();
        $status = $this->setStatus($info['start_at'], $info['end_at']);
        if ($status == XsLikeIcon::WAIT_STATUS) {
            $info['start_at'] = $endTime;
        }

        $data = [
            'id'          => $id,
            'name'        => $info['name'],
            'area_config' => json_decode($info['area_config'], true),
            'icon_config' => json_decode($info['icon_config'], true),
            'start_at'    => $info['start_at'],
            'end_at'      => $endTime
        ];

        list($res, $msg) = (new PsService())->editLikeIcon($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $id, 'before_json' => $info, 'after_json' => $data];
    }

    public function info(int $id): array
    {
        $info = XsLikeIcon::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前配置不存在');
        }

        $info['area_config'] = json_decode( $info['area_config'], true);
        $info['area_config'] = array_map('strval', $info['area_config']);
        $info['start_at'] = Helper::now($info['start_at']);
        // 最大结束时间直接给空
        if ($info['end_at'] == 4294967295) {
            $info['end_at'] = '';
        } else {
            $info['end_at'] = Helper::now($info['end_at']);
        }
        $iconArr = json_decode($info['icon_config'], true);
        foreach ($iconArr as $icon) {
            $info['material'][] = [
                'img' => $icon,
                'img_url' => Helper::getHeadUrl($icon)
            ];
        }

        return $info;
    }

    private function setStatus(int $start, int $end): int
    {
        $status = XsLikeIcon::END_STATUS;
        $now = time();
        if ($start > $now) {
            $status = XsLikeIcon::WAIT_STATUS;
        } else if ($now > $start && $now < $end) {
            $status = XsLikeIcon::HAVE_STATUS;
        }
        return $status;
    }
}