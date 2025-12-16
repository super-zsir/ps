<?php

namespace Imee\Service\Domain\Service\Csms\Process\Staff;

use Imee\Models\Xss\CsmsStaff;
use Imee\Service\Domain\Service\Csms\Context\Staff\StaffAddContext;

class StaffAddProcess
{
    public function __construct(StaffAddContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {

        $userId = $this->context->userId;
        $app_ids = $this->context->appId;
        $languages = $this->context->language;
        $areas = $this->context->area;

        // 用户app_id
        $app_ids = $app_ids ? implode(',', $app_ids) : '';
        $area = $areas ? implode(',', $areas) : '';
        $language = $languages ? implode(',', $languages) : '';

        $csmsStaff = CsmsStaff::findFirst([
            'conditions' => 'user_id = :user_id:',
            'bind'       => ['user_id' => $userId]
        ]);
        if ($csmsStaff) {
            $csmsStaff->app_id = $app_ids;
            $csmsStaff->language = $language;
            $csmsStaff->area = $area;
            $csmsStaff->update_time = time();
            $csmsStaff->save();
        } else {
            $csmsStaff = new CsmsStaff();
            $csmsStaff->save([
                'user_id'     => $userId,
                'state'       => CsmsStaff::STATE_WORK,
                'app_id'      => $app_ids,
                'language'    => $language,
                'area'        => $area,
                'manager'     => is_numeric($this->context->manager) ? $this->context->manager : CsmsStaff::MANAGER_NO,
                'create_time' => time(),
                'update_time' => time()
            ]);
        }
        return true;
    }

}