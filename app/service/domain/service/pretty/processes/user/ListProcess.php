<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\User;

use Imee\Service\Domain\Context\Pretty\User\ListContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsUserPretty;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Models\xsst\XsstUserPrettyExtend;

/**
 * 列表
 */
class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;
    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsUserPretty::class;
        $this->query = XsUserPretty::query();
    }

    protected function buildWhere()
    {
        if (!empty($this->context->uid)) {
            $this->where['condition'][] = "uid=:uid:";
            $this->where['bind']['uid'] = $this->context->uid;
        }

        if (!empty($this->context->prettyUid)) {
            $this->where['condition'][] = "pretty_uid = :pretty_uid:";
            $this->where['bind']['pretty_uid'] = $this->context->prettyUid;
        }

        if (is_numeric($this->context->prettySource)) {
            $this->where['condition'][] = "pretty_source = :pretty_source:";
            $this->where['bind']['pretty_source'] = $this->context->prettySource;
        }

        if (!empty($this->context->status)) {
            if ($this->context->status == XsUserPretty::STATUS_VALID) {
                $this->where['condition'][] = "expire_time > :expire_time:";
            } else {
                $this->where['condition'][] = "expire_time <= :expire_time:";
            }
            $this->where['bind']['expire_time'] = time();
        }

        if (!empty($this->context->datelineSdate)) {
            $this->where['condition'][] = "dateline >= :dateline_start:";
            $this->where['bind']['dateline_start'] = strtotime($this->context->datelineSdate);
        }

        if (!empty($this->context->datelineEdate)) {
            $this->where['condition'][] = "dateline < :dateline_end:";
            $this->where['bind']['dateline_end'] = strtotime($this->context->datelineEdate) + 86400;
        }
        if (!empty($this->context->maxId)) {
            $this->where['condition'][] = "id < :max_id:";
            $this->where['bind']['max_id'] = $this->context->maxId;
        }
    }

    protected function formatList($items)
    {
        $format = [];
        $uids = [];
        $ids = [];
        if (empty($items)) {
            return $format;
        }
        foreach ($items as $item) {
            $tmp = $item->toArray();
            $uids[] = $item->uid;
            $ids[] = $item->id;
            $tmp['display_status'] = $item->displayStatus();
            $format[] = $tmp;
        }

        if (empty($format)) {
            return $format;
        }
        $userMap = $this->getUserInfoModel($uids)->handle();
        $extends = XsstUserPrettyExtend::find([
            'conditions' => 'pid in({ids:array})',
            'bind' => [
                'ids' => $ids,
            ],
        ])->toArray();
        $extendMap = array_column($extends, null, 'pid');
        foreach ($format as &$v) {
            $v['user_name'] = isset($userMap[$v['uid']]) ? $userMap[$v['uid']]['name'] : '';
            $v['mark'] = isset($extendMap[$v['id']]) ? $extendMap[$v['id']]['mark'] : '';
            $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
            $v['expire_time'] = $v['expire_time'] > 0 ? date('Y-m-d H:i:s', $v['expire_time']) : '';
            $v['display_pretty_source'] = isset(XsUserPretty::$displayPrettySource[$v['pretty_source']])
                ? XsUserPretty::$displayPrettySource[$v['pretty_source']] : '';
        }
        return $format;
    }
}
