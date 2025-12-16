<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Models\Xss\CsmsTaskLog;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class TaskListProcess extends NormalListAbstract
{

    use CsmsTrait;


    public function __construct(PageContext $context)
    {
        parent::__construct($context);
        $this->masterClass = CsmsTaskLog::class;
        $this->query = CsmsTaskLog::query();
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
        if(is_numeric($this->context->appId)){
            $this->where['condition'][] = "app_id = :app_id:";
            $this->where['bind']['app_id'] = intval($this->context->appId);
        }
        if($this->context->choice){
            $this->where['condition'][] = "choice = :choice:";
            $this->where['bind']['choice'] = $this->context->choice;
        }
        if($this->context->uid){
            $this->where['condition'][] = "uid = :uid:";
            $this->where['bind']['uid'] = $this->context->uid;
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
                $item['app_name'] = $this->getAppName($item['app_id']);
                $item['choice_name'] = $this->getChoiceName($item['choice']);
//                $item['check_data'] = json_decode($item['check_data'], true);
//                $item['format_data'] = json_decode($item['format_data']);
//                $item['clean_data'] = json_decode($item['clean_data'], true);
                $item['time'] = date('Y-m-d H:i:s', $item['dateline']);
            }
        }
        return $items;
    }

}