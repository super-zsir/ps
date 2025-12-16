<?php

namespace Imee\Service\Operate\Medal;

use Imee\Comp\Common\Redis\RedisSimple;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsMedalResource;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class MedalService
{
    /** @var PsService $rpc */
    protected $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getList(array $params, int $page, int $pageSize): array
    {
        $conditions = $this->getConditions($params);

        $res = XsMedalResource::getList($conditions, '*', $page, $pageSize);
        if ($res['total'] == 0) {
            return [];
        }
        $prefix = XsMedalResource::PREFIX;
        $bigAreaList = XsBigarea::getAllBigAreaCode();

        $allGift = $this->getGiftMap();

        foreach ($res['data'] as &$v) {
            $v['type'] = (string)$v['type'];
            if ($v['type'] == 1) {
                $v['big_area'] = '-';
            }
            $v['online_status'] = $v['online_status'] + 1;
            foreach (['image_1', 'image_2', 'image_3'] as $image) {
                $v[$image . '_all'] = Helper::getHeadUrl($v[$image]);
            }
            foreach ($bigAreaList as $bigArea) {
                if ($bigArea == 'cn') {
                    $key = $prefix . 'zh_tw';
                } else {
                    $key = $prefix . $bigArea;
                }
                if (!isset($v[$key])) {
                    continue;
                }
                $desc = json_decode($v[$key], true);
                $v[$bigArea . '_name'] = $desc['name'] ?? '';
                $v[$bigArea . '_description'] = $desc['description'] ?? '';
            }
            $v['name'] = $v['cn_name'] ?? '';
            $v['description'] = $v['cn_description'] ?? '';

            if ($v['type'] == XsMedalResource::GIFT_MEDAL) {
                $v['medal_group_show'] = empty($allGift[$v['medal_group_id']]) ? '' : $allGift[$v['medal_group_id']];
            } else {
                $v['medal_group_show'] = '';
            }

            $v['task_value'] = $v['task_value'] ?: '';
        }
        return $res;
    }

    public function add(array $params): array
    {
        $bigAreaList = XsBigarea::getAllBigAreaCode();
        $data = $this->formatData($params);
        if ($params['big_area'] == 'all' && in_array($params['type'], [XsMedalResource::HONOR_MEDAL, XsMedalResource::GIFT_MEDAL])) {
            foreach ($bigAreaList as $v) {
                XsMedalResource::add(array_merge(['big_area' => $v], $data));
            }
            $this->rpc->userMedalUpdateConfig(['medal_type'=>intval($data['type']), 'area_name'=>$bigAreaList]);
        } else {
            if ($params['big_area'] == 'all') {
                $params['big_area'] = 'cn';
            }
            XsMedalResource::add(array_merge(['big_area' => $params['big_area']], $data));
            $this->rpc->userMedalUpdateConfig(['medal_type' => intval($data['type']), 'area_name' => [$params['big_area']]]);
        }
        return [true, ''];
    }

    public function edit(array $params): array
    {
        $bigAreaList = XsBigarea::getAllBigAreaCode();
        $medalInfo = XsMedalResource::findOne($params['id']);
        if (!$medalInfo) {
            return [false, '当前勋章不存在'];
        }
        $data = $this->formatData($params, $medalInfo);
        if ($params['big_area'] == 'all' && in_array($medalInfo['type'], [XsMedalResource::HONOR_MEDAL, XsMedalResource::GIFT_MEDAL])) {
            foreach ($bigAreaList as $v) {
                if ($v == $medalInfo['big_area']) {
                    XsMedalResource::edit($medalInfo['id'], array_merge(['big_area' => $v], $data));
                } else {
                    XsMedalResource::add(array_merge(['big_area' => $v], $data));
                }
            }
            $this->rpc->userMedalUpdateConfig(['medal_type' => intval($data['type']), 'area_name' => $bigAreaList]);

        } else {
            $bigArea = $params['big_area'];
            if (!in_array($params['big_area'], $bigAreaList)) {
                $bigArea = 'cn';
            }
            XsMedalResource::edit($params['id'], array_merge(['big_area' => $bigArea], $data));

            $areaName = [$bigArea];
            $medalInfo['big_area'] != $bigArea && $areaName[] = $medalInfo['big_area'];
            $this->rpc->userMedalUpdateConfig(['medal_type' => intval($data['type']), 'area_name' => $areaName]);
        }
        return [true, ''];
    }

    private function formatData(array $params, array $medal = []): array
    {
        $params = array_filter($params);
        $data = [
            'type'        => $params['type'] ?? '',
            'jump_url'    => $params['jump_url'] ?? '',
            'image_1'     => $params['image_1'],
            'image_2'     => $params['image_2'],
            'image_3'     => $params['image_3'],
            'operator'    => Helper::getAdminName($params['admin_uid']),
            'update_time' => date('Y-m-d H:i:s', time())
        ];
        
        if (isset($params['medal_group_id']) &&
            isset($params['task_value']) &&
            isset($medal['type']) &&
            $medal['type'] == XsMedalResource::GIFT_MEDAL &&
            (
                $medal['medal_group_id'] != $params['medal_group_id'] ||
                $medal['task_value'] != $params['task_value'])) {
            throw new ApiException(ApiException::MSG_ERROR, '【需要赠送的礼物id-名称】【需要赠送的礼物数量】字段不支持编辑');
        }

        if ($params['type'] == XsMedalResource::GIFT_MEDAL) {

            if (empty($params['medal_group_id']) || empty($params['task_value'])) {
                throw new ApiException(ApiException::MSG_ERROR, '【需要赠送的礼物id-名称】【需要赠送的礼物数量】字段不能为空');
            }

            $data['medal_group_id'] = $params['medal_group_id'] ?? 0;
            $data['task_value'] = $params['task_value'] ?? 0;
            $data['jump_url'] = '';
        } else {
            $data['medal_group_id'] = 0;
            $data['task_value'] = 0;

            if (empty($params['cn_description']) || empty($params['en_description'])) {
                throw new ApiException(ApiException::MSG_ERROR, '【勋章描述-中文】【勋章描述-英文】字段不能为空');
            }
        }
        $emptyDescription = empty($medal) && $params['type'] == XsMedalResource::GIFT_MEDAL;

        $bigAreaList = XsBigarea::getAllBigAreaCode();
        $prefix = XsMedalResource::PREFIX;
        $descriptions = [];
        $nameSuffix = '_name';
        $descSuffix = '_description';
        foreach ($bigAreaList as $v) {
            if ($v == 'cn') {
                $dataKey = $prefix . 'zh_tw';
            } else {
                $dataKey = $prefix . $v;
            }
            if (array_key_exists($v . $nameSuffix, $params)) {
                $descriptions[$dataKey] = json_encode([
                    'name'        => $params[$v . $nameSuffix],
                    'description' => $emptyDescription ? '' : ($params[$v . $descSuffix] ?? '')
                ]);
            }
            if (isset($medal[$dataKey]) && !empty($medal[$dataKey])) {
                if (!array_key_exists($v . $nameSuffix, $params)) {
                    $descriptions[$dataKey] = json_encode([
                        'name'        => '',
                        'description' => ''
                    ]);
                }
            }
        }
        return array_merge($data, $descriptions);
    }


    public function put(int $id)
    {
        $model = XsMedalResource::findOne($id);
        if (!$model) {
            return [false, '当前勋章不存在'];
        }

        list($flg, $rec) = XsMedalResource::edit($id, [
            'online_status' => 1
        ]);

        $flg && $this->rpc->userMedalUpdateConfig(['medal_type' => intval($model['type']), 'area_name' => [$model['big_area']]]);

        return [$flg, $rec];
    }

    public function lower(int $id): array
    {
        $model = XsMedalResource::findOne($id);
        if (!$model) {
            return [false, '当前勋章不存在'];
        }

        list($flg, $rec) = XsMedalResource::edit($id, [
            'online_status' => 0
        ]);

        $flg && $this->rpc->userMedalUpdateConfig(['medal_type' => intval($model['type']), 'area_name' => [$model['big_area']]]);

        return [$flg, $rec];
    }

    private function getConditions(array $params): array
    {
        $conditions = $bind = [];
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = 'id = :id:';
            $bind['id'] = $params['id'];
        }
        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(description_zh_tw, '$.name')) LIKE :name:";
            $bind['name'] = "%{$params['name']}%";
        }
        if (isset($params['type']) && !empty($params['type'])) {
            $conditions[] = 'type = :type:';
            $bind['type'] = $params['type'];
        }
        if (isset($params['big_area']) &&
            !empty($params['big_area']) &&
            $params['big_area'] != 'all') {
            $conditions[] = 'big_area = :big_area:';
            $bind['big_area'] = $params['big_area'];
        }
        if (isset($params['online_status']) && !empty($params['online_status'])) {
            $conditions[] = 'online_status = :online_status:';
            $bind['online_status'] = $params['online_status'] - 1;
        }
        return compact('conditions', 'bind');
    }

    public static function getTypeMap($value = null, string $format = '')
    {
        $map = XsMedalResource::$typeMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getGiftMap($value = null, string $format = '')
    {
        $data = XsGift::getListByWhere([['deleted', '=', XsGift::DELETE_NO], ['is_customized', '=', 0]], 'id, name');
        $map = [];
        foreach ($data as $v) {
            $map[$v['id']] = sprintf('%s-%s', $v['id'], $v['name']);
        }
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }



}
