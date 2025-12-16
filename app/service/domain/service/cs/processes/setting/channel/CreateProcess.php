<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\Channel;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\Cs\ChannelException;
use Imee\Service\Domain\Context\Cs\Setting\Channel\CreateContext;
use Imee\Service\Helper;
use Phalcon\Di;

class CreateProcess
{
    private $context;
    public function __construct(CreateContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $uid = $this->context->uid;
        $services = $this->context->service;

        $user = CmsUser::findFirst($uid);
        if (!$user || $user->user_status < 1) {
			ChannelException::throwException(ChannelException::USER_NOT_FOUND);
        }

        $conn = Di::getDefault()->getShared('cms');
        $conn->begin();
        try {
            $conn->execute("delete from cms_chat_service where user_id = {$uid}");
            if (is_array($services) && count($services) > 0) {
                $services = array_values(array_unique(array_values(array_map('intval', $services))));
                foreach ($services as $service) {
                    $service = intval($service);
					if ($service >= 10000001 && $service <= 10000022) {
                        $conn->execute("insert into cms_chat_service (user_id, service) values ($uid, $service)");
                    }
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            Helper::debugger()->error(__CLASS__ .' : '. $e->getMessage());
			ChannelException::throwException(ChannelException::CREATE_FAIL_ERROR);
        }
        return true;
    }
}
