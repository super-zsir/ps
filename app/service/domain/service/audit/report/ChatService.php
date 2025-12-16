<?php

namespace Imee\Service\Domain\Service\Audit\Report;

use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Domain\Service\Audit\Report\Context\ChatContext;
use Imee\Service\Domain\Service\Audit\Report\Processes\ChatProcess;

class ChatService
{
    use SingletonTrait;

    /**
     * @var ChatContext
     */
    private $context;

    /**
     * @param array $params
     * @return $this
     */
    public function setContext(array $params): ChatService
    {
        $this->context = new ChatContext($params);
        return $this;
    }

    /**
     * @return array
     */
    public function chatList()
    {
        $process = new ChatProcess();
        if ($this->context->to > 0) {
            $sid = min($this->context->from, $this->context->to) . '-' . max($this->context->from, $this->context->to);
            $names = array();
            $profile = XsUserProfile::findFirst($this->context->from);
            $names[$this->context->from] = $profile->name ?? '';
            $profile = XsUserProfile::findFirst($this->context->to);
            $names[$this->context->to] = $profile->name ?? '';

            $this->context->setParams(array(
                'sid' => $sid,
                'sort' => 'id',
                'dir' => 'asc',
            ));
            $list = $process->getList($this->context);
            $res = $list['data'];
            if ($res) {
                foreach ($res as &$rec) {
                    $rec['dateline'] = date('Y-n-d H:i:s', $rec['dateline']);
                    $rec['from_name'] = $names[$rec['from_user_id']];
                    $rec['to_name'] = $names[$rec['to_user_id']];
                }
            }
            return array('data' => $res, 'total' => $list['total']);
        } else {
            $profile = XsUserProfile::findFirst($this->context->from);
            $this->context->setParams(array(
                'from_user_id' => $this->context->from,
                'sort' => 'id',
                'dir' => 'desc',
            ));
            $list = $process->getList($this->context);
            $res = $list['data'];
            if ($res) {
                foreach ($res as &$rec) {
                    $rec['dateline'] = date('Y-n-d H:i:s', $rec['dateline']);
                    $rec['from_name'] = $profile->name ?? '';
                    $rec['to_name'] = $rec['to_user_id'];
                }
            }
            return array('data' => $res, 'total' => $list['total']);
        }
    }
}