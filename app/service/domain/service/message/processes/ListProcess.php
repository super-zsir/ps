<?php
namespace Imee\Service\Domain\Service\Message\Processes;

use Imee\Models\Xss\XsChatMessageNew;
use Imee\Service\Domain\Context\Message\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsChatMessageNew::class;
        $this->query = XsChatMessageNew::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

        $from = $this->context->from;
        $to = $this->context->to;

        if ($to > 0) {
            $sid = min($from, $to) . '-' . max($from, $to);
            $where['condition'][] = 'sid = :sid:';
            $where['bind']['sid'] = $sid;
        } else {
            $where['condition'][] = 'from_user_id = :from_user_id:';
            $where['bind']['from_user_id'] = $from;
        }

        $this->where = $where;
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $res = $items->toArray();

        $uids = [$this->context->from];
        if ($this->context->to > 0) {
            $uids[] = $this->context->to;
        }
        $userInfoMap = $this->getUserInfoModel($uids)->handle();

        foreach ($res as &$v) {
            $v['dateline'] = date('Y-n-d H:i:s', $v['dateline']);
            $v['from_name'] = isset($userInfoMap[$v['from_user_id']]) ? $userInfoMap[$v['from_user_id']]['name'] : '';
            $v['to_name'] = $this->context->to > 0 && $userInfoMap[$v['to_user_id']] ?
                $userInfoMap[$v['to_user_id']]['name'] : $v['to_user_id'];
        }

        return $res;
    }
}
