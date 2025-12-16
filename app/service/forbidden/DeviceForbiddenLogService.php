<?php


namespace Imee\Service\Forbidden;


use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xs\XsDeviceForbiddenLog;

class DeviceForbiddenLogService
{


    public $objectType = [
        '1' => 'mac',
        '2' => 'did',
    ];

    public $type = [
        '1' => '封禁',
        '2' => '解封',
    ];


    public $source = [
        'user_list'  => '用户列表',
        'login_info' => '登录列表',
    ];

    public $duration = [
        '0'         => '不封禁',
        '7200'      => '2小时',
        '14400'     => '4小时',
        '28800'     => '8小时',
        '43200'     => '12小时',
        '86400'     => '一天',
        '259200'    => '三天',
        '604800'    => '一周',
        '2592000'   => '一个月',
        '315360000' => '永久',
    ];

    public $reason = [
        'disrupt_platform'         => '扰乱平台秩序',
        'pornographic_information' => '发布色情信息',
        'fraudulent_information'   => '发布诈骗信息',
        'third_party_ads'          => '发布第三方广告',
        'abuse_harass_users'       => '侮辱谩骂骚扰用户',
        'steal_information'        => '盗用他人信息',
        'pretend_official'         => '冒充官方账号',
        'system_blocked_mistake'   => '系统误封',
    ];

    public function list($params = [])
    {
        $res = ['data' => [], 'total' => 0];
        $where = [];
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 30;
        if (isset($params['time_sdate']) && $params['time_sdate']) {
            $where[] = ['dateline', '>=', strtotime(trim($params['time_sdate']))];
        }
        if (isset($params['time_edate']) && $params['time_edate']) {
            $where[] = ['dateline', '<', strtotime(trim($params['time_edate'])) + 86400];
        }
        $objectType = $params['object_type'] ?? '';
        $objectId = $params['object_id'] ?? '';
        if ($objectType && $objectId) {
            $where[] = ['object_type', '=', trim($objectType)];
            $where[] = ['object_id', '=', trim($objectId)];
        }
        if (isset($params['source']) && $params['source']) {
            $where[] = ['source', '=', trim($params['source'])];
        }
        $res = XsDeviceForbiddenLog::getListAndTotal($where, '*', 'id desc', $page, $limit);
        if ($res['total']) {
            $ops = array_values(array_unique(array_column($res['data'], 'op')));
            $cmsUser = CmsUser::getListByWhere([
                ['user_id', 'in', $ops]
            ]);
            $cmsUser = array_column($cmsUser, null, 'user_id');
            foreach ($res['data'] as &$item) {
                $item['dateline'] = $item['dateline'] ? date("Y-m-d H:i:s", $item['dateline']) : '';
                $item['object_type_name'] = $this->objectType[$item['object_type']] ?? '';
                $item['type_name'] = $this->type[$item['type']] ?? '';
                $item['source_name'] = $this->source[$item['source']] ?? $item['source'];
                $item['reason_name'] = $this->reason[$item['reason']] ?? $item['reason'];
                $item['duration_name'] = $this->duration[$item['duration']] ?? $item['duration'];
                $item['op_name'] = isset($cmsUser[$item['op']]) ? $cmsUser[$item['op']]['user_name'] : '';
            }
        }
        return $res;
    }


    public function getSource()
    {
        $format = [];
        $source = $this->source;
        foreach ($source as $key => $value) {
            $format[] = ['value' => $key, 'label' => $value];
        }
        return $format;
    }


    public function getObjectType()
    {
        $format = [];
        $objectType = $this->objectType;
        foreach ($objectType as $key => $value) {
            $format[] = ['value' => $key, 'label' => $value];
        }
        return $format;
    }


    public function getDuration()
    {
        $format = [];
        foreach ($this->duration as $key => $value) {
            $format[] = ['value' => $key, 'label' => $value];
        }
        return $format;
    }


    public function getReason()
    {
        $format = [];
        foreach ($this->reason as $key => $value) {
            $format[] = ['value' => $key, 'label' => $value];
        }
        return $format;
    }

}