<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;

use Imee\Models\Xs\XsCircleReport;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

class TaskListProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;
    protected $leftClass;
    protected $query;
    protected $esPrimaryKey = 'id';

    public function __construct(PageContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsCircleReport::class;
        $this->query = XsCircleReport::query();
    }


    protected function buildWhere()
    {
        $rotype = $this->context->rotype ?: '';
        $roid = $this->context->roid ?: 0;
        $uid = $this->context->uid ?: 0;
        $rpids = $this->context->rpids ?: [];
        $status = $this->context->status;

        $ruid = $this->context->ruid ?: 0;

        if ($uid > 0) {
            $this->where['condition'][] = "$this->masterClass.uid = ".$uid;
            $this->esWhere['uid'] = $uid;
        }

        if ($this->context->ruid) {
            $this->where['condition'][] = "$this->masterClass.ruid = ".$this->context->ruid;
        }

        if($roid){
            $this->where['condition'][] = "$this->masterClass.roid = ".$roid;
        }

        if ($rpids) {
            $this->where['condition'][] = "$this->masterClass.rpid in (".implode(',', $rpids).")";
            $this->esWhere['in']['rpid'] = $rpids;
        }

        if ($rotype) {
            $this->where['condition'][] = "$this->masterClass.rotype = '".$rotype."'";
            $this->esWhere['rotype'] = $rotype;
        }


        // 前面做状态转换
        if ($status) {
            $this->where['condition'][] = "$this->masterClass.status = '".$status."'";
            $this->esWhere['status'] = $status;
        }

        if($this->context->language){
            $this->where['condition'][] = "$this->masterClass.language = :language:";
            $this->where['bind']['language'] = $this->context->language;
        }
    }


//    protected function setColumns()
//    {
//        $this->query->columns(
//            "$this->masterClass.rpid,
//			$this->masterClass.uid,
//			$this->masterClass.type,
//			$this->masterClass.ruid,
//			$this->masterClass.rotype,
//			$this->masterClass.roid,
//			$this->masterClass.tpid,
//			$this->masterClass.[desc],
//			$this->masterClass.pics,
//			$this->masterClass.status,
//			$this->masterClass.create_time,
//			$this->masterClass.update_time"
//        );
//    }





    protected function formatList($items)
    {
        if ($items) {
            $items = $items->toArray();
            $uids = array_column($items, 'uid');
            $ruids = array_column($items, 'ruid');
            $uids = array_merge($uids, $ruids);
            $userInfo = $this->getUserInfos($uids);
            foreach ($items as &$item) {
                $item['user_name'] = isset($userInfo[$item['uid']]) ? $userInfo[$item['uid']]['name'] : '';
                $item['to_name'] = isset($userInfo[$item['ruid']]) ? $userInfo[$item['ruid']]['name'] : '';
                $item['date'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
                $item['status_name'] = XsCircleReport::$status[$item['status']];
                $item['rotype_name'] = XsCircleReport::$rotype[$item['rotype']];
                $item['language_name'] = Helper::getLanguageName($item['language']);

            }
        }
        return $items;
    }


    public function getUserInfos($uids)
    {
        if(empty($uids)) return [];
        $users = XsUserProfile::find([
            'conditions' => 'uid in ({uids:array})',
            'bind' => [
                'uids' => $uids
            ]
        ])->toArray();
        return array_column($users, null, 'uid');
    }
}
