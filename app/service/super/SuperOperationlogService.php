<?php

namespace Imee\Service\Super;

use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\SuperImages;
use Imee\Service\Helper;
use Imee\Service\Lesscode\Traits\SingletonTrait;
use Imee\Exception\ApiException;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUserBigarea;

class SuperOperationlogService
{
    use SingletonTrait;

    /**
     * @param array $params
     * @return array
     */
    public function list(array $params = []): array
    {
        $res = ['data' => [], 'total' => 0];

        $start = isset($params['time_sdate']) ? strtotime($params['time_sdate']) : '';
        $end = isset($params['time_edate']) ? strtotime($params['time_edate']) + 86400 : '';
        
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 15;

        $adminBigareaIds = CmsModuleUserBigarea::getBigareaList($params['admin_uid'], false);
        if (empty($adminBigareaIds)) {
            return $res;
        }
        $adminBigareaIds = array_keys($adminBigareaIds);

        if (!empty($params['bigarea_id'])) {            
            if (!in_array($params['bigarea_id'], $adminBigareaIds)) {
                return $res;
            }
            $bigarea_id = [intval($params['bigarea_id'])];
        } else {
            $bigarea_id = array_map('intval', $adminBigareaIds);
        }

        $data = [
            'start_time' => $start,
            'end_time' => $end,
            'operator_uid' => $params['operator_uid'] ?? '',
            'admin_uid' => $params['admin'] ?? '',
            'room_type' => $params['room_type'] ?? '',
            'rid' => $params['rid'] ?? '',
            'uid' => $params['uid'] ?? '',
            'bigarea_id' => $bigarea_id ?? [],
            'operate_type' => $params['operate_type'] ?? '',
            'page' => [
                'page_index' => (int) ($page),
                'page_size' => (int) ($limit),
            ],
        ];

        $data = array_filter($data, function ($value) {
            return $value !== '' || is_array($value);
        });

        [$res, $code] = (new PsRpc())->call(PsRpc::API_QUERY_APP_ADMIN_OPERATE_LOG, [
            'json' => $data
        ]);

        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            throw new ApiException(ApiException::MSG_ERROR, $res['common']['msg'] ?? '接口错误');
        }

        $list = $res['list'] ?? [];
        $total = $res['page']['total_count'] ?? 0;

        foreach ($list as &$rec) {
            $rec['dateline'] = date('Y-m-d H:i:s', $rec['create_time']);
            $rec['admin'] = $rec['admin_uid'];
            $rec['admin_name'] = Helper::getAdminName($rec['admin_uid']);
            $rec['room_type_name'] = SuperImages::$roomType[$rec['room_type']] ?? '';
            $rec['bigarea_name'] = XsBigarea::getAllNewBigArea()[$rec['bigarea_id']] ?? '';
            $rec['operate_type_name'] = SuperImages::$operateType[$rec['operate_type']] ?? '';
        }

        return ['data' => $list, 'total' => $total];
    }

    public function log($params = []): array
    {
        [$res, $code] = (new PsRpc())->call(PsRpc::API_QUERY_APP_ADMIN_OPERATE_LOG_DETAIL, [
            'json' => [
                'id' => $params['id'] ?? 0,
            ]
        ]);

        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            throw new ApiException(ApiException::MSG_ERROR, $res['common']['msg'] ?? '接口错误');
        }

        $data = $res['data'] ?? [];

        $data['dateline'] = date('Y-m-d H:i:s', $data['create_time']);
        $data['room_type_name'] = SuperImages::$roomType[$data['room_type']] ?? '';
        $data['operate_type_name'] = SuperImages::$operateType[$data['operate_type']] ?? '';

        $extra = $data['extra'];
        if($data['operate_type'] == SuperImages::OPERATE_TYPE_ROOM_BOTTOM && isset($extra['room_bottom'])){
            $data['room_dateline'] = date('Y-m-d H:i:s',  $extra['room_bottom']['dateline'] ?? '') ?? ' ';
            $data['reason'] = $extra['room_bottom']['reason'] ?? ' ';
        }

        if($data['operate_type'] == SuperImages::OPERATE_TYPE_CLOSE_ROOM && isset($extra['close_room'])){
            $data['reason'] = $extra['close_room']['reason'] ?? ' ';
            $data['remark'] = $extra['close_room']['remark'] ?? ' ';
        }

        if($data['operate_type'] == SuperImages::OPERATE_TYPE_FORBIDDEN_ROOM && isset($extra['forbidden_room'])){
            $data['reason'] = $extra['forbidden_room']['reason'] ?? ' ';
            $data['remark'] = $extra['forbidden_room']['remark'] ?? ' ';
            $data['duration'] = Helper::formatDuration($extra['forbidden_room']['duration'] ?? 0) ?? ' ';
        }

        if($data['operate_type'] == SuperImages::OPERATE_TYPE_REMOVE_ROOM_FEED && isset($extra['remove_feed'])){
            $data['bigarea_name'] = XsBigarea::getAllNewBigArea()[$data['bigarea_id']] ?? ' ';
            $data['reason'] = $extra['remove_feed']['reason'] ?? ' ';
            $data['duration'] = Helper::formatDuration($extra['remove_feed']['duration'] ?? 0) ?? ' ';
        }

        if($data['operate_type'] == SuperImages::OPERATE_TYPE_CHANGE_ROOM_ICON && isset($extra['changed_room_icon'])){
            $icon = isset($extra['changed_room_icon']['icon']) && !empty($extra['changed_room_icon']['icon']) ? $extra['changed_room_icon']['icon'] : ' ';
            $data['icon'] = Helper::getHeadUrl($icon);
        }

        if($data['operate_type'] == SuperImages::OPERATE_TYPE_ACTIVE_CHECK && isset($extra['active_check'])){
            $data['popup_duration'] = Helper::formatDuration($extra['active_check']['duration'] ?? 0);
            $data['popup_status'] = $extra['active_check']['status'] ?? ' ';
            $data['popup_update_time'] = date('Y-m-d H:i:s',  $extra['active_check']['update_time'] ?? '') ?? ' ';
            if($extra['active_check']['status'] == '弹窗持续中'){
                $data['popup_update_time'] = ' ';
            }
        }

        $listData = [
            '操作时间' => [
                'type' => 'text',
                'value' => $data['dateline'] ?? '-',
            ],
            '操作内容' => [
                'type' => 'text',
                'value' => $data['operate_type_name'] ?? '-',
            ],
            '房间id' => [
                'type' => 'text',
                'value' => $data['rid'] ?? '-',
            ],
            '房主uid' => [
                'type' => 'text',
                'value' => $data['uid'] ?? '-',
            ],
            '房主昵称' => [
                'type' => 'text',
                'value' => $data['user_name'] ?? '-',
            ],
            '房间类型' => [
                'type' => 'text',
                'value' => $data['room_type_name'] ?? '-',
            ],
            '大区' => [
                'type' => 'text',
                'value' => $data['bigarea_name'] ?? '',
            ],
            '置底时间' => [
                'type' => 'text',
                'value' => $data['room_dateline'] ?? '',
            ],
            '封禁时长' => [
                'type' => 'text',
                'value' => $data['duration'] ?? '',
            ],
            '原因' => [
                'type' => 'text',
                'value' => $data['reason'] ?? '',
            ],
            '备注' => [
                'type' => 'text',
                'value' => $data['remark'] ?? '',
            ],
            '更换前的封面' => [
                'type' => 'image',
                'value' => $data['icon'] ?? '',
            ],
            '弹窗可确认时长' => [
                'type' => 'text',
                'value' => $data['popup_duration'] ?? '',
            ],
            '弹窗状态' => [
                'type' => 'text',
                'value' => $data['popup_status'] ?? '',
            ],
            '弹窗状态变更时间' => [
                'type' => 'text',
                'value' => $data['popup_update_time'] ?? '',
            ],
        ];

        $listData = array_filter($listData, function ($item) {
            return isset($item['value']) && $item['value'] !== '';
        });

        return ['data' => $listData, 'total' => 1];
    }

    public function operateTypeConfig()
    {
        $res = SuperImages::$operateType;
        return Helper::getFormatConfig($res);
    }

    public function roomTypeConfig()
    {
        $res = SuperImages::$roomType;
        return Helper::getFormatConfig($res);
    }
}