<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\User;

use Imee\Service\Domain\Context\Pretty\User\HistoryContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsUserPretty;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Models\xsst\XsstUserPrettyExtend;
use Imee\Models\Xsst\BmsOperateHistory;

/**
 * 列表
 */
class HistoryProcess extends NormalListAbstract
{
    use UserInfoTrait;
    public function __construct(HistoryContext $context)
    {
        $this->context = $context;
        $this->masterClass = BmsOperateHistory::class;
        $this->query = BmsOperateHistory::query();
    }

    protected function buildWhere()
    {
        if (!empty($this->context->id)) {
            $this->where['condition'][] = "sid=:sid:";
            $this->where['bind']['sid'] = $this->context->id;
        }

        $this->where['condition'][] = "source = :source:";
        $this->where['bind']['source'] = BmsOperateHistory::$source[BmsOperateHistory::PRETTY_NUM];
    }

    protected function formatList($items)
    {
        $format = [];
        $staffUids = [];

        if (empty($items)) {
            return $format;
        }
        foreach ($items as $item) {
            $tmp = $item->toArray();
            $staffUids[] = $item->update_uid;

            $format[] = $tmp;
        }

        if (empty($format)) {
            return $format;
        }
        $staffMap = $this->getStaffBaseInfos($staffUids);

        foreach ($format as &$v) {
            $v['staff_name'] = isset($staffMap[$v['update_uid']]) ? $staffMap[$v['update_uid']]['user_name'] : '';
            if (!empty($v['content'])) {
                $content = json_decode($v['content'], true);
                if (isset($content['id'])) {
                    unset($content['id']);
                }
                $v = array_merge($v, $content);
            }
            $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
        }
        return $format;
    }
}
