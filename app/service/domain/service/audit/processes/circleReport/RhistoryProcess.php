<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;


use Imee\Models\Xs\XsCircleReport;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class RhistoryProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;

    public function __construct(PageContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsCircleReport::class;
        $this->query = XsCircleReport::query();
    }

    public function buildWhere()
    {
        // TODO: Implement buildWhere() method.
        $rpid = $this->context->rpid;
        $rec = XsCircleReport::findFirst($rpid);
        if($rec){
           $rotype = $rec->rotype;
           $roid = $rec->roid;

           $this->where['condition'][] = 'roid=:roid: and rotype=:rotype:';
           $this->where['bind']['roid'] = $roid;
           $this->where['bind']['rotype'] = $rotype;
        }
    }

    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        if ($items) {
            $items = $items->toArray();
            $uids = array_column($items, 'uid');
            $users = $this->getUserInfoModel($uids)->handle();
            foreach ($items as &$item) {
                $item['name'] = isset($users[$item['uid']]) ? $users[$item['uid']]['name'] : '';
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            }
        }
        return $items;
    }

}