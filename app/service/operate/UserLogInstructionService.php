<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsUserLogInstruction;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class UserLogInstructionService
{
    /**
     * @var PsService $rpc
     */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $bigareaId = intval(array_get($params, 'big_area_id', 0));
        $logType = intval(array_get($params, 'log_type', 0));
        $status = intval(array_get($params, 'status', 0));

        $start = trim(array_get($params, 'time_sdate', ''));
        $end = trim(array_get($params, 'time_edate', ''));

        $start = $start ? strtotime($start) : 0;
        $end = $end ? strtotime($end) : 0;


        $query = [];
        $id && $query[] = ['id', '=', $id];
        $uid && $query[] = ['uid', '=', $uid];
        $bigareaId && $query[] = ['big_area_id', '=', $bigareaId];
        $logType && $query[] = ['log_type', '=', $logType];
        $status && $query[] = ['status', '=', $status];
        $start && $query[] = ['create_time', '>=', $start];
        $end && $query[] = ['create_time', '<=', $end];

        $data = XsUserLogInstruction::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $_create = array_get($rec, 'create_time', 0);
            $rec['create_time'] = $_create ? date('Y-m-d H:i:s', $_create) : '';
        }
        return $data;
    }

    public function add($params): array
    {
        $uid = intval(array_get($params, 'uid', 0));
        $logType = intval(array_get($params, 'log_type', 0));
        $adminId = intval(array_get($params, 'admin_id', 0));

        $operator = Helper::getAdminName($adminId ?: '');

        $profile = XsUserProfile::findOne($uid);
        if (!$profile) {
            return [false, '用户不存在'];
        }
        $uuid = create_uuid();


        $data = [
            'uuid'     => $uuid,
            'uid'      => $uid,
            'log_type' => $logType,
            'operator' => $operator,
        ];

        list($flg, $rec) = $this->rpc->createUploadLogInstruction($data);

        return [$flg, $flg ? ['after_json' => $data] : $rec];
    }

    public function getStatusMap($value = null, $format = '')
    {
        $map = XsUserLogInstruction::$statusMap;

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

    public function getLogTypeMap($value = null, $format = '')
    {
        $map = XsUserLogInstruction::$logTypeMap;

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

}