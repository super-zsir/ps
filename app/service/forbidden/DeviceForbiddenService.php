<?php


namespace Imee\Service\Forbidden;


use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Service\Helper;

class DeviceForbiddenService
{


    public $type = [
        1 => '封禁',
        2 => '解封',
    ];

    public function getType()
    {
        $format = [];
        foreach ($this->type as $key => $value) {
            $format[] = ['value' => $key, 'label' => $value];
        }
        return $format;
    }


    public $forbiddenType = [
        'mac' => 1,
        'did' => 2
    ];

    public function forbidden($params = [])
    {
        $objectType = $params['object_type'] ?? '';
        if (!$objectType || !in_array($objectType, ['mac', 'did'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'object_type error');
        }
        $objectId = $params[$objectType] ?? '';
        if (!$objectId) {
            throw new ApiException(ApiException::MSG_ERROR, 'object_id error');
        }
        $type = $params['type'] ?? '';
        if (!$type || !in_array($type, [1, 2])) {
            throw new ApiException(ApiException::MSG_ERROR, 'type error');
        }
        if ($type == 1) {
            $duration = $params['duration'] ?? '';
            $reason = $params['reason'] ?? '';
            if (!$duration || !$reason) {
                throw new ApiException(ApiException::MSG_ERROR, 'device forbidden time and reason must select');
            }
        }

        $forbiddenData = [
            'object_type' => (int)$this->forbiddenType[$objectType],
            'type'        => (int)$type,
            'object_id'   => (string)trim($objectId),
            'duration'    => (int)($params['duration'] ?? 0),
            'source'      => (string)($params['source'] ?? 'login_info'),
            'reason'      => (string)($params['reason'] ?? ''),
            'mark'        => (string)($params['mark'] ?? ''),
            'op'          => (int)$params['admin_uid']
        ];
        $psRpc = new PsRpc();
        [$res, $_] = $psRpc->call(PsRpc::API_DEVICE_FORBIDDEN, ['json' => $forbiddenData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        throw new ApiException(ApiException::MSG_ERROR, $res['common']['msg']);

    }


}