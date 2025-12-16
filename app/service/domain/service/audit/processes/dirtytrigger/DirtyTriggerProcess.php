<?php


namespace Imee\Service\Domain\Service\Audit\Processes\Dirtytrigger;


use Imee\Models\Xss\XssDirtyTrigger;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

class DirtyTriggerProcess extends NormalListAbstract
{

    public function __construct(PageContext $context)
    {
        parent::__construct($context);
        $this->masterClass = XssDirtyTrigger::class;
        $this->query = XssDirtyTrigger::query();
    }


    public function buildWhere()
    {
        // TODO: Implement buildWhere() method.
        if(is_numeric($this->context->uid)){
            $this->where['condition'][] = "$this->masterClass.uid = :uid:";
            $this->where['bind']['uid'] = $this->context->uid;
        }

        if($this->context->source){
            $this->where['condition'][] = "$this->masterClass.source = :source:";
            $this->where['bind']['source'] = $this->context->source;
        }

        if(!empty($this->context->beginTime)){
            $this->where['condition'][] = "dateline >= :begin_time:";
            $this->where['bind']['begin_time'] = strtotime($this->context->beginTime);
        }

        if (!empty($this->context->endTime)) {
            $this->where['condition'][] = "dateline < :end_time:";
            $this->where['bind']['end_time'] = strtotime($this->context->endTime) + 86400;
        }

    }

    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        if($items){
            $items = is_array($items) ? $items : $items->toArray();
            foreach ($items as &$item){
                $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
                $item['source_name'] = isset(XssDirtyTrigger::$source[$item['source']]) ? XssDirtyTrigger::$source[$item['source']] : '';
            }
        }
        return $items;
    }
}
