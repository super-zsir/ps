<?php


namespace Imee\Service\Domain\Service\Audit\Processes\Dirtysum;


use Imee\Models\Xsst\XsstDirtyTriggerCount;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Helper;

class DirtySumListProcess extends NormalListAbstract
{


    public function __construct(PageContext $context)
    {
        parent::__construct($context);
        $this->masterClass = XsstDirtyTriggerCount::class;
        $this->query = XsstDirtyTriggerCount::query();
    }


    public function buildWhere()
    {
        // TODO: Implement buildWhere() method.
        if(!empty($this->context->text)){
            $this->where['condition'][] = "$this->masterClass.text = :text:";
            $this->where['bind']['text'] = $this->context->text;
        }

        if($this->context->datetype){
            $this->where['condition'][] = "$this->masterClass.datetype = :datetype:";
            $this->where['bind']['datetype'] = $this->context->datetype;
        }

        if($this->context->datetype == 1){
            if($this->context->start){
                $this->where['condition'][] = "$this->masterClass.dateline = :start:";
                $this->where['bind']['start'] = strtotime($this->context->start);
            }else{
                $this->where['condition'][] = "$this->masterClass.dateline >= :start:";
                $this->where['bind']['start'] = (strtotime(date("Y-m-d")) - 86400);
            }
        }else{
            $today = strtotime(date("Y-m-d"));
            if ($this->context->start) {
                $startData = Helper::getOtherDateWeekDur(strtotime($this->context->start));

                $this->where['condition'][] = "$this->masterClass.dateline = :start:";
                $this->where['bind']['start'] = $startData['start'];
            } else {
                $startData = Helper::getOtherDateWeekDur($today);
                $this->where['condition'][] = "$this->masterClass.dateline >= :start:";
                $this->where['bind']['start'] = $startData['start'];
            }
        }

    }


    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        if($items){
            $items = is_array($items) ? $items : $items->toArray();
            foreach ($items as &$item){
                $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
                $item['language_name'] = Helper::getLanguageName($item['language']);
                $item['datetype_name'] = XsstDirtyTriggerCount::$datetype[$item['datetype']];
            }
        }
        return $items;
    }



}