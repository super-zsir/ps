<?php

namespace Imee\Service\Domain\Service\Csms\Process\Staff;

use Imee\Exception\ApiException;
use Imee\Models\Xss\CsmsStaff;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Csms\Context\Staff\StaffEditContext;
use Imee\Service\Domain\Service\Csms\CsmsTaskService;
use Imee\Service\Domain\Service\Csms\Exception\CsmsStaffException;

class StaffEditProcess
{
    public function __construct(StaffEditContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $userId = $this->context->userId;
        $appId = $this->context->appId;
        $languages = $this->context->language;
        $areas = $this->context->area;

        $staff = CsmsStaff::findFirst([
            'conditions' => 'user_id = :user_id:',
            'bind'       => [
                'user_id' => $userId
            ]
        ]);
        if (!$staff) {
            CsmsStaffException::throwException(CsmsStaffException::STAFF_NOT_EXIST);
        }
        $staff->app_id = $appId ? implode(',', $appId) : '';
        $staff->language = $languages ? implode(',', $languages) : '';
        $staff->area = $areas ? implode(',', $areas) : '';

        $staff->manager = is_numeric($this->context->manager) ? $this->context->manager : CsmsStaff::MANAGER_NO;
        $staff->save();

        // 编辑员工审核项权限
        $service = new CsmsTaskService();
        if ($this->context->choices) {
            //新增权限
            foreach ($this->context->choices as $moduleChoice) {
                $moduleChoice = explode('-', $moduleChoice);
                $power = [
                    'module'  => $moduleChoice[0],
                    'choice'  => $moduleChoice[1],
                    'user_id' => $userId
                ];
                $service->staffAdd($power);
            }

            //删除权限
            $condition = [];
            $condition[] = ['user_id', '=', $userId];
            $userChoiceArr = CsmsUserChoice::useMaster()->getListByWhere($condition);
            foreach ($userChoiceArr as $item) {
                $tmpModuleChoice = $item['module'] . '-' . $item['choice'];
                if (in_array($tmpModuleChoice, $this->context->choices)) {
                    continue;
                }
                $power = [
                    'module'  => $item['module'],
                    'choice'  => $item['choice'],
                    'user_id' => $userId
                ];
                $service->staffDel($power);
            }
        } else {
            throw new ApiException(ApiException::MSG_ERROR, '权限不能全部清空！');
        }
    }
}