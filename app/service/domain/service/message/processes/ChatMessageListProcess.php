<?php
namespace Imee\Service\Domain\Service\Message\Processes;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xss\XsChatMessageNew;
use Imee\Models\Xsst\XsstAutoQuestionLog;
use Imee\Service\Domain\Context\Message\ChatMessageListContext;

class ChatMessageListProcess
{
    protected $context;

    public function __construct(ChatMessageListContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $sid = $this->context->sid;

        list($service, $uid) = explode('-', $sid);
        if (intval($service) <= 0 || intval($uid) <= 0) {
            return [];
        }

        $autoChat = XsstAutoQuestionLog::find(array(
            "uid = :uid: and service = :service:",
            "bind" => array("uid" => $uid, "service" => $service),
            "order" => 'id asc'
        ))->toArray();
        if ($autoChat) {
            foreach ($autoChat as &$value) {
                $value['admin_name'] = 'AUTO-CHAT';
                $value['channel_type'] = 'ROBOT';
                $value['object_name'] = 'RC:TxtMsg';
            }
        }


        $res = XsChatMessageNew::find(array(
            "sid = :sid:",
            "bind" => array("sid" => $sid),
            'order' => 'id asc',
        ));
        $res = $res->toArray();
        $map = array();
        foreach ($res as $index => $val) {
            $message = json_decode($val['content'], true);
            if (isset($message['extra'])) {
                $extra = json_decode($message['extra'], true);
                if ($extra && isset($extra['_admin'])) {
                    $map[$index] = intval($extra['_admin']);
                }
            }
        }
        $uids = array_unique(array_values($map));
        if (!empty($uids)) {
            $users = CmsUser::find("user_id in (" . implode(',', $uids) . ")");
            $userMap = array();
            foreach ($users as $user) {
                $uid = intval($user->user_id);
                $userMap[$uid] = $user->user_name;
            }
            foreach ($res as $index => &$item) {
                $item['admin_name'] = '';
                if (isset($map[$index])) {
                    $uid = $map[$index];
                    if (isset($userMap[$uid])) {
                        $item['admin_name'] = $userMap[$uid];
                    }
                }
            }
        }
        //按时间排序
        $data = array_merge($res, $autoChat);
        $dateline = array_column($data, 'dateline');
        array_multisort($dateline, $data);

        return $data;
    }
}
