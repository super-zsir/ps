<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Helper\Constant\CsmsConstant;
use Imee\Service\Domain\Service\Audit\ModuleChoiceService;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Helper;

class CsmsToolConfigProcess
{

    use CsmsTrait;

    public function handle()
    {

        $format = [];
        foreach (Helper::getAllApp() as $k => $v) {
            $tmp = [];
            $tmp['label'] = $v;
            $tmp['value'] = (string)$k;
            $format['app_id'][] = $tmp;
        }

        $choices = $this->getAllChoices();
        foreach ($choices as $key => $value) {
            $tmp = [];
            $tmp['label'] = $value['choice_name'];
            $tmp['value'] = $value['choice'];
            $format['choice'][] = $tmp;
        }

        foreach (CsmsConstant::$csms_review as $key => $value) {
            $format['review'][] = ['label' => $value, 'value' => $key];
        }

        foreach (CsmsConstant::$csms_change_source as $key => $value){
            $format['source'][] = ['label' => $value, 'value' => $key];
        }

        foreach (CsmsConstant::$csms_change_type as $key => $value){
            $format['type'][] = ['label' => $value, 'value' => $key];
        }

        $format['result'] = [
            ['label' => '成功', 'value' => 1],
            ['label' => '失败', 'value' => 0],
        ];

        $format['state'] = [
            ['label' => '成功', 'value' => 1],
            ['label' => '失败', 'value' => 0],
        ];

        return $format;

    }

}