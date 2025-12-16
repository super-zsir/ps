<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\User;

use Imee\Service\Domain\Context\Pretty\User\HistoryContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsPrettyInfoLog;
use Imee\Models\Xs\XsUserPretty;

use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Models\xsst\XsstUserPrettyExtend;
use Imee\Models\Xsst\BmsOperateHistory;

/**
 * 列表
 */
class InfoLogProcess extends NormalListAbstract
{
    use UserInfoTrait;
    public function __construct(HistoryContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsPrettyInfoLog::class;
        $this->query = XsPrettyInfoLog::query();
    }

    protected function buildWhere()
    {
        $uid = 0;
        if (!empty($this->context->id)) {
            $model = XsUserPretty::findFirst([
                'conditions' => 'id = :id:',
                'bind' => array(
                    'id' => $this->context->id,
                )
            ]);
            if ($model) {
                $uid = $model->uid;
            }
        }

        $this->where['condition'][] = "uid = :uid:";
        $this->where['bind']['uid'] = $uid;
    }

    protected function formatList($items)
    {
        $format = [];


        if (empty($items)) {
            return $format;
        }
        foreach ($items as $item) {
            $tmp = $item->toArray();


            $format[] = $tmp;
        }

        if (empty($format)) {
            return $format;
        }


        foreach ($format as &$v) {
            $v['display_reason'] = isset(XsPrettyInfoLog::$displayReason[$v['reason']]) ?
                XsPrettyInfoLog::$displayReason[$v['reason']] : '';

            $v['create_dateline'] = $v['create_dateline'] > 0 ? date('Y-m-d H:i:s', $v['create_dateline']) : '';

            $v['before_expire_dateline'] = $v['before_expire_dateline'] > 0 ?
                date('Y-m-d H:i:s', $v['before_expire_dateline']) : '';

            $v['after_expire_dateline'] = $v['after_expire_dateline'] > 0 ?
                date('Y-m-d H:i:s', $v['after_expire_dateline']) : '';
        }
        return $format;
    }
}
