<?php
namespace Imee\Service\Domain\Service\Cs\Processes\Workbench;

use Imee\Exception\Cs\WorkbenchException;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xss\XsChatSession;
use Imee\Models\Xss\XssAutoService;
use Imee\Service\Domain\Context\Cs\Workbench\ActiveServiceContext;
use Imee\Service\Helper;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class ActiveServiceProcess
{
    use UserInfoTrait;
    private $context;

    public function __construct(ActiveServiceContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $uid = $this->context->uid;
        $fromId = $this->context->fromId;

        if ($fromId <= 0) {
            $fromId = XssAutoService::SERVICE_CONSULTATION;
        }

		$userMap = $this->getUserInfoModel([$uid])
			->language()
			->vip()
			->title()
			->handle();

        if (!isset($userMap[$uid])) {
			WorkbenchException::throwException(WorkbenchException::USER_NOT_FOUND);
        }
        $session_data = XsChatSession::addRow($uid, $fromId);

        $data = [
            'uid' => $uid,
            'name' => $userMap[$uid]['name'],
            'icon' => Helper::getHeadUrl($userMap[$uid]['icon']),
            'title' => $userMap[$uid]['title'],
            'vip' => $userMap[$uid]['vip'],
            'app_id' => $userMap[$uid]['app_id'],
            'app_name' => Helper::getAppName($userMap[$uid]['app_id']),
            'deleted' => $userMap[$uid]['deleted'],
            'dateline' => $userMap[$uid]['dateline'],
        ];

        if (!empty($session_data)) {
            $data['unread'] = $session_data['unread'];
            $data['changed'] = $session_data['changed'];
        }

        return $data;
    }
}
