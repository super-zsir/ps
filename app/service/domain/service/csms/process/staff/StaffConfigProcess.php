<?php

namespace Imee\Service\Domain\Service\Csms\Process\Staff;

use Imee\Comp\Operate\Auth\Service\StaffService;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsProduct;
use Imee\Models\Xss\CsmsStaff;
use Imee\Service\Helper;

class StaffConfigProcess
{
    public function handle()
    {
        $res = [];
        $service = new StaffService();
        $cmsUser = $service->getAllStaff();
        if ($cmsUser) {
            foreach ($cmsUser as $user) {
                $res['staff'][] = [
                    'label' => $user['user_name'],
                    'value' => $user['user_id']
                ];
            }
        }

        $apps = CsmsProduct::find([
            'conditions' => 'state = :state:',
            'bind' => [
                'state' => CsmsProduct::STATUS_NORMAL
            ]
        ])->toArray();

        foreach ($apps as $k => $v) {
            $tmp['label'] = $v['name'];
            $tmp['value'] = (string)$v['app_id'];
            $res['app'][] = $tmp;
        }

        foreach (CsmsStaff::$manager as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = (string)$k;
            $res['manager'][] = $tmp;
        }

        // 获取所有审核阶段 审核项
        $stageChoice = CsmsChoiceStage::find([
            'conditions' => 'state = :state:',
            'bind' => [
                'state' => CsmsChoiceStage::STATUS_NORMAL
            ]
        ])->toArray();
        if ($stageChoice) {
            $stage = array_column($stageChoice, 'stage');
            $choice = array_column($stageChoice, 'choice');

            $modules = CsmsModules::find([
                'conditions' => 'module in ({modules:array})',
                'bind' => [
                    'modules' => $stage
                ]
            ])->toArray();
            $modules = array_column($modules, null, 'module');
            $choices = CsmsChoice::find([
                'conditions' => 'choice in ({choice:array})',
                'bind' => [
                    'choice' => $choice
                ]
            ])->toArray();
            $choices = array_column($choices, null, 'choice');
            foreach ($stageChoice as $item) {
                $res['choices'][] = [
                    'label' => $modules[$item['stage']]['module_name'] . '-' . $choices[$item['choice']]['choice_name'],
                    'value' => $item['stage'] . '-' . $item['choice']
                ];
            }
        }
        // 获取所有语言
        foreach (Helper::getLanguageArr() as $key => $value) {
            $res['language'][] = ['label' => $value, 'value' => $key];
        }

        // 获取所有大区
        foreach (XsBigarea::getBigAreaList() as $key => $value) {
            $res['area'][] = ['label' => $value, 'value' => $key];
        }

        return $res;
    }
}