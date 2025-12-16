<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Models\Xss\CsmsImageScan;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class ImageListProcess extends NormalListAbstract
{

    use CsmsTrait;

    public function __construct(PageContext $context)
    {
        parent::__construct($context);
        $this->masterClass = CsmsImageScan::class;
        $this->query = CsmsImageScan::query();
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

        if($this->context->servicer){
            $this->where['condition'][] = "servicer = :servicer:";
            $this->where['bind']['servicer'] = $this->context->servicer;
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
            $this->where['condition'][] = "pk_value = :pk_value:";
            $this->where['bind']['pk_value'] = $this->context->pkValue;
        }
    }

    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        if($items){
            $items = is_array($items) ? $items : $items->toArray();
            foreach ($items as &$item){
                $item['choice_name'] = $this->getChoiceName($item['choice']);
                $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
            }
        }
        return $items;
    }

}