<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\UserCustomize;

use Imee\Service\Domain\Context\Pretty\UserCustomize\ListContext;
use Imee\Models\Xs\XsUserCustomizePretty;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 列表
 */
class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;
    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsUserCustomizePretty::class;
        $this->query = XsUserCustomizePretty::query();
    }


    protected function buildWhere()
    {
        if (!empty($this->context->id)) {
            $this->where['condition'][] = "id=:id:";
            $this->where['bind']['id'] = $this->context->id;
        }

        if (!empty($this->context->customizePrettyId)) {
            $this->where['condition'][] = "customize_pretty_id = :customize_pretty_id:";
            $this->where['bind']['customize_pretty_id'] = $this->context->customizePrettyId;
        }

        if (!empty($this->context->uid)) {
            $this->where['condition'][] = "uid = :uid:";
            $this->where['bind']['uid'] = $this->context->uid;
        }

        if (is_numeric($this->context->status)) {
            if ($this->context->status == XsUserCustomizePretty::STATUS_EXPIRE) {
                $this->where['condition'][] = "status = :status:";
                $this->where['bind']['status'] = XsUserCustomizePretty::STATUS_INIT;

                $this->where['condition'][] = "qualification_expire_dateline <= :qualification_expire_dateline:";
                $this->where['bind']['qualification_expire_dateline'] = time();
            } elseif ($this->context->status == XsUserCustomizePretty::STATUS_INIT) {
                $this->where['condition'][] = "status = :status:";
                $this->where['bind']['status'] = XsUserCustomizePretty::STATUS_INIT;

                $this->where['condition'][] = "qualification_expire_dateline > :qualification_expire_dateline:";
                $this->where['bind']['qualification_expire_dateline'] = time();
            } else {
                $this->where['condition'][] = "status = :status:";
                $this->where['bind']['status'] = $this->context->status;
            }
        }

        if (!empty($this->context->createDatelineSdate)) {
            $this->where['condition'][] = "create_dateline >= :create_dateline_start:";
            $this->where['bind']['create_dateline_start'] = strtotime($this->context->createDatelineSdate);
        }

        if (!empty($this->context->createDatelineEdate)) {
            $this->where['condition'][] = "create_dateline < :create_dateline_end:";
            $this->where['bind']['create_dateline_end'] = strtotime($this->context->createDatelineEdate) + 86400;
        }
        if (!empty($this->context->maxId)) {
            $this->where['condition'][] = "id < :max_id:";
            $this->where['bind']['max_id'] = $this->context->maxId;
        }

        if (is_numeric($this->context->giveType)) {
            $this->where['condition'][] = "give_type = :give_type:";
            $this->where['bind']['give_type'] = intval($this->context->giveType);
        }

        if (is_numeric($this->context->source)) {
            $this->where['condition'][] = "source = :source:";
            $this->where['bind']['source'] = intval($this->context->source);
        }

        if (!empty($this->context->sourceId)) {
            $this->where['condition'][] = "source_id = :source_id:";
            $this->where['bind']['source_id'] = intval($this->context->sourceId);
        }
    }

    protected function formatList($items)
    {
        $format = [];
        $uids = [];
        $styleIds = [];
        if (empty($items)) {
            return $format;
        }
        foreach ($items as $item) {
            $tmp = $item->toArray();
            $uids[] = $item->uid;
            $styleIds[] = $item->customize_pretty_id;
            $format[] = $tmp;
        }
        if (empty($format)) {
            return $format;
        }
        $userMap = $this->getUserInfoModel($uids)->handle();

        $styleList = XsCustomizePrettyStyle::find([
            'conditions' => 'id in({ids:array})',
            'bind' => [
                'ids' => $styleIds,
            ],
        ])->toArray();
        $styleMap = array_column($styleList, null, 'id');
        foreach ($format as &$v) {
            $v['uid_str'] = $v['uid'];
            $v['user_name'] = isset($userMap[$v['uid']]) ? $userMap[$v['uid']]['name'] : '';
            $v['style_name'] = isset($styleMap[$v['customize_pretty_id']]) ? $styleMap[$v['customize_pretty_id']]['name'] : '';
                        
            if ($v['status'] == XsUserCustomizePretty::STATUS_INIT && $v['qualification_expire_dateline'] <= time()) {
                $v['status'] = XsUserCustomizePretty::STATUS_EXPIRE;
            }
            $v['display_status'] = XsUserCustomizePretty::$displayStatus[$v['status']] ?? '';
            $v['qualification_expire_dateline'] = date('Y-m-d H:i:s', $v['qualification_expire_dateline']);
            $v['create_dateline'] = $v['create_dateline'] > 0 ? date('Y-m-d H:i:s', $v['create_dateline']) : '';
            $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
        }
        return $format;
    }
}
