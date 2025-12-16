<?php

namespace Imee\Service\Operate\Relieveforbiddencard;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserPropCard;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RelieveForbiddenCardSearchService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params, int $page, int $pageSize): array
    {
        $list = XsUserPropCard::getListJoinTable(
            $this->getConditions($params),
            [],
            $this->getColumns(),
            'count(distinct u.uid, u.prop_card_id) as cnt',
            'hold_num desc',
            $page,
            $pageSize,
            'u.uid,u.prop_card_id'
        );
        if ($list['total'] == 0) {
            return [];
        }

        $userBigAreaList = XsUserBigarea::getUserBigareas(Helper::arrayFilter($list['data'], 'uid'));
        foreach ($list['data'] as &$item) {
            $item['bigarea_id'] = $userBigAreaList[$item['uid']] ?? '';
        }

        return $list;
    }

    private function getColumns(): array
    {
        return [
            'u.uid',
            'sum(if(u.expired_time >= UNIX_TIMESTAMP(), u.num, 0)) as hold_num',
            'sum(if(u.expired_time < UNIX_TIMESTAMP(), u.num, 0)) as expire_num',
            'u.prop_card_id',
        ];
    }

    public function recover(array $params): array
    {
        $data = $this->valid($params);
        list($res, $msg) = $this->rpcService->recyclePropCard($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('rpc: %s', $msg));
        }

        return ['after_json' => $data];
    }

    private function valid(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $holdNum = intval($params['hold_num'] ?? 0);
        $num = intval($params['num'] ?? 0);
        $cid = intval($params['prop_card_id'] ?? 0);
        $cardType = intval($params['card_type'] ?? 0);

        if (empty($cid) || empty($uid)) {
            throw new ApiException(ApiException::MSG_ERROR, '参数配置错误');
        }
        if ($num > $holdNum) {
            throw new ApiException(ApiException::MSG_ERROR, '回收数量不能大于背包数量');
        }

        $data = [
            'prop_card_id' => $cid,
            'num'          => $num,
            'uid'          => $uid,
        ];

        $cardType && $data['card_type'] = $cardType;

        return $data;
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['u.prop_card_type', '=', XsPropCardConfig::TYPE_RELIEVE_FORBIDDEN_CARD],
        ];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['u.uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['u.dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['u.dateline', '<', strtotime($params['dateline_edate']) + 86399];
        }

        return $conditions;
    }
}