<?php

namespace Imee\Service\Operate\Relieveforbiddencard;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsSendPropCardLog;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Operate\Cp\PropCardService;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class RelieveForbiddenCardSendService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params, $source = XsSendPropCardLog::SOURCE_ADMIN_SEND): array
    {
        $conditions = $this->getConditions($params, $source);
        $list = XsSendPropCardLog::getListAndTotal($conditions, '*', 'dateline desc, id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        if ($source == XsSendPropCardLog::SOURCE_GIVE) {
            $userBigAreaList = XsUserBigarea::getUserBigareas(Helper::arrayFilter($list['data'], 'uid'));
        }
        foreach ($list['data'] as &$item) {
            // 4294967295 表示永久有效
            if ($item['expired_time'] != 4294967295) {
                $effectDay = ceil(($item['expired_time'] - $item['dateline']) / 86400);
                $item['expired_time'] = max($effectDay, 0);
            } else {
                $item['expired_time'] = '永久';
            }
            $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
            $source == XsSendPropCardLog::SOURCE_GIVE && $item['bigarea_id'] = $userBigAreaList[$item['uid']] ?? '';
        }
        return $list;
    }

    public function getRelieveForbiddenCardMap(): array
    {
        return StatusService::formatMap(XsPropCard::getOptions(), 'label,value');
    }

    public function create(array $params): array
    {
        $data = $this->valid($params);
        $admin = Helper::getAdminName($params['admin_uid']);
        list($res, $msg) = $this->rpcService->sendPropCard([$data], $admin);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('rpc: %s', $msg));
        }

        return ['after_json' => $data];
    }

    public function addBatch(array $params): array
    {
        $list = [];
        foreach ($params as $item) {
            $list[] = $this->valid($item);
        }
        $admin = Helper::getAdminName(Helper::getSystemUid());
        list($res, $msg) = $this->rpcService->sendPropCard($list, $admin);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('rpc: %s', $msg));
        }

        return ['after_json' => $list];
    }

    private function valid(array $params): array
    {
        $uid = trim($params['uid'] ?? '');
        $uidArr = Helper::formatIdString($uid);
        $cid = intval($params['prop_card_id'] ?? 0);
        $num = intval($params['num'] ?? '');
        $expiredTime = intval($params['expired_time'] ?? 0);
        $remark = trim($params['remark'] ?? '');

        if (empty($uidArr) || empty($cid) || $num < 0) {
            throw new ApiException(ApiException::MSG_ERROR, '参数配置错误');
        }

        if (!is_numeric($params['expired_time']) || $expiredTime < -1) {
            throw new ApiException(ApiException::MSG_ERROR, '有效期必须填写整数且不得小于-1');
        }

        $errorUid = XsUserProfile::checkUid($uidArr);

        if ($errorUid && is_array($errorUid)) {
            throw new ApiException(ApiException::MSG_ERROR, implode(',', $errorUid) . '以上UID错误');
        }

        return [
            'uids'         => implode(',', $uidArr),
            'prop_card_id' => $cid,
            'num'          => $num,
            'validity_day' => $expiredTime,
            'remark'       => $remark,
        ];
    }

    private function getConditions(array $params, int $source): array
    {
        $conditions = [
            ['source', '=', $source],
            ['prop_card_type', '=', XsPropCardConfig::TYPE_RELIEVE_FORBIDDEN_CARD],
            ['sender', $source == XsSendPropCardLog::SOURCE_ADMIN_SEND ? '=' : '!=', 0]
        ];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86399];
        }

        return $conditions;
    }
}