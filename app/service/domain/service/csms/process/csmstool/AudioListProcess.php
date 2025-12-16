<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsAudioLog;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class AudioListProcess extends NormalListAbstract
{

    use CsmsTrait;

    public function __construct(PageContext $context)
    {
        parent::__construct($context);
        $this->masterClass = CsmsAudioLog::class;
        $this->query = CsmsAudioLog::query();
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


        if($this->context->choice){
            $this->where['condition'][] = "choice = :choice:";
            $this->where['bind']['choice'] = $this->context->choice;
        }

        if($this->context->taskId){
            $this->where['condition'][] = "taskid = :taskid:";
            $this->where['bind']['taskid'] = $this->context->taskId;
        }

        if($this->context->pkValue){
            $this->where['condition'][] = "pk = :pk_value:";
            $this->where['bind']['pk_value'] = $this->context->pkValue;
        }
    }


    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        $items = is_array($items) ? $items : $items->toArray();
        foreach ($items as &$item){
            $item['choice_name'] = $this->getChoiceName($item['choice']);
            $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
            $item['audio'] = $this->getTypeValue($item['audio'], CsmsConstant::TYPE_AUDIO);
        }
        return $items;
    }
}