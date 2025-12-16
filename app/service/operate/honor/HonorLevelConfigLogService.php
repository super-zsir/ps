<?php

namespace Imee\Service\Operate\Honor;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserHonorLevelSendRecord;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class HonorLevelConfigLogService
{
    /** @var PsService $rpc */
    protected $rpc;


    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = (int)array_get($params, 'limit', 15);
        $page = (int)array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $configId = intval(array_get($params, 'config_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $startTime = trim(array_get($params, 'create_time_sdate', ''));
        $endTime = trim(array_get($params, 'create_time_edate', ''));

        $startTime = $startTime ? strtotime($startTime) : 0;
        $endTime = $endTime ? strtotime($endTime . ' 23:59:59') : 0;


        $query = ['page' => $page,  'limit' => $limit];
//        $id && $query['id'] = $id;
        $configId && $query['config_id'] = $configId;
        $uid && $query['uid'] = $uid;
        $startTime && $query['start_time'] = $startTime;
        $endTime && $query['end_time'] = $endTime;

        list($flg, $msg, $data) = $this->rpc->honorLevelSendList($query);
        if (!$flg) {
            return ['data' => [], 'total' => 0];
        }

        foreach ($data['data'] as &$rec) {
            $_create = $rec['create_time'] ?? 0;
            $_styleConfig = $rec['style_config'] ?? [];


            $rec['level_show'] = sprintf('%s-%s', $rec['min_level'] ?? '', $rec['max_level'] ?? '');
            $rec['create_time'] = $_create ? date('Y-m-d H:i:s', $_create) : '';
            $rec['level_icon_show'] = Helper::getHeadUrl($_styleConfig['level_icon'] ?? '');
            $rec['style_icon_show'] = Helper::getHeadUrl($_styleConfig['style_icon'] ?? '');
            $rec['font_color_show'] = implode(',', $_styleConfig['font_color'] ?? []);
            $rec['send_source'] = isset(XsUserHonorLevelSendRecord::$sourceMap[$rec['send_source']]) ? XsUserHonorLevelSendRecord::$sourceMap[$rec['send_source']] : '';
        }
        return $data;
    }

    public function add(array $params, $adminId = ''): array
    {
        $uid = str_replace('，', ',', trim($params['uid'] ?? ''));
        $uidArr = explode(',', $uid);
        $data = [];
        foreach ($uidArr as $_uid) {
            $params['uid'] = $_uid;
            $data[] = $this->validateAndFormatData($params);
        }
        list($flg, $msg, $data) = $this->rpc->honorLevelBatchSend([
            'list'        => $data,
            'operator'    => Helper::getAdminName($adminId),
            'send_source' => XsUserHonorLevelSendRecord::SOURCE_BACKEND
        ]);

        return [$flg, $flg ? ['after_json' => array_merge($data)] : $msg];
    }

    public function addBatch(array $params, $adminId = ''): array
    {
        $data = [];
        foreach ($params as $rec) {
            $data[] = $this->validateAndFormatData($rec);
        }

        list($flg, $msg, $data) = $this->rpc->honorLevelBatchSend([
            'list'        => $data,
            'operator'    => Helper::getAdminName($adminId),
            'send_source' => XsUserHonorLevelSendRecord::SOURCE_BACKEND,
        ]);

        return [$flg, $flg ? ['after_json' => array_merge($data)] : $msg];
    }

    private function validateAndFormatData($params): array
    {
        $uid = intval(array_get($params, 'uid', 0));
        $sendLevel = intval(array_get($params, 'send_level', 0));
        $remark = trim(array_get($params, 'remark', ''));

        $user = XsUserProfile::findOne($uid);
        if (empty($user)) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('用户[%d]不存在', $uid));
        }

        return [
            'uid'        => $uid,
            'send_level' => $sendLevel,
            'remark'     => $remark,
        ];
    }

    public function getInfo($params): array
    {
        $level = intval(array_get($params, 'send_level', 0));

        list($flg, $msg, $data) = $this->rpc->honorLevelGetConfig([
            'honor_level' => $level
        ]);
        if (!$flg) {
            return [];
        }

        return [
            'config_id'       => intval($data['id']),
            'level_icon'      => isset($data['style_config']['level_icon']) ? Helper::getHeadUrl($data['style_config']['level_icon']) : '',
            'style_icon'      => isset($data['style_config']['style_icon']) ? Helper::getHeadUrl($data['style_config']['style_icon']) : '',
            'color_str'       => isset($data['style_config']['color_str']) ? $data['style_config']['color_str'] : '',
            'font_color'      => isset($data['style_config']['font_color']) ? $data['style_config']['font_color'] : '',
            'shade_style'     => isset($data['style_config']['shade_style']) ? $data['style_config']['shade_style'] : '',
            'shade_direction' => isset($data['style_config']['shade_direction']) ? $data['style_config']['shade_direction'] : '',
        ];
    }

    public static function getSourceMap($value = null, string $format = '')
    {
        $map = XsUserHonorLevelSendRecord::$sourceMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

}