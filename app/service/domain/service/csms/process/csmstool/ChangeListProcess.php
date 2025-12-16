<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsChange;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class ChangeListProcess extends NormalListAbstract
{


    use CsmsTrait;


    public function __construct(PageContext $context)
    {
        parent::__construct($context);
        $this->masterClass = CsmsChange::class;
        $this->query = CsmsChange::query();
    }

    public function buildWhere()
    {
        // TODO: Implement buildWhere() method.
        if($this->context->beginTime){
            $this->where['condition'][] = "dateline >= :begin_time:";
            $this->where['bind']['begin_time'] = strtotime($this->context->beginTime);
        }
        if($this->context->endTime){
            $this->where['condition'][] = "dateline < :end_time:";
            $this->where['bind']['end_time'] = strtotime($this->context->endTime) + 86400;
        }
        if(is_numeric($this->context->choice)){
            $this->where['condition'][] = "choice = :choice:";
            $this->where['bind']['choice'] = intval($this->context->choice);
        }
        if($this->context->pkValue){
            $this->where['condition'][] = "pk_value = :pk_value:";
            $this->where['bind']['pk_value'] = $this->context->pkValue;
        }
        if($this->context->source){
            $this->where['condition'][] = "source = :source:";
            $this->where['bind']['source'] = $this->context->source;
        }
        if($this->context->type){
            $this->where['condition'][] = "type = :type:";
            $this->where['bind']['type'] = $this->context->type;
        }
        if(is_numeric($this->context->result)){
            $this->where['condition'][] = "result = :result:";
            $this->where['bind']['result'] = $this->context->result;
        }

    }

    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        if($items){
            $items = is_array($items) ? $items : $items->toArray();
            if($items){
                foreach ($items as &$item){
                    $item['source_name'] = CsmsConstant::$csms_change_source[$item['source']];
                    $item['choice_name'] = $this->getChoiceName($item['choice']);
                    $item['result'] = $item['result'] ? '成功' : '失败';
                    $item['type_name'] = CsmsConstant::$csms_change_type[$item['type']];
                    $item['time'] = date('Y-m-d H:i:s', $item['dateline']);
                }
            }
        }
        return $items;
    }




}