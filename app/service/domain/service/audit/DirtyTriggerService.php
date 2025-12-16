<?php


namespace Imee\Service\Domain\Service\Audit;

use Imee\Models\Xss\XssDirtyTrigger;
use Imee\Service\Domain\Context\Audit\Dirtytrigger\DirtyTriggerListContext;
use Imee\Service\Domain\Service\Audit\Processes\Dirtytrigger\DirtyTriggerProcess;

class DirtyTriggerService
{


    /**
     * 敏感词触发列表
     * @param DirtyTriggerListContext $context
     * @return array
     */
    public function list(DirtyTriggerListContext $context)
    {
        $process = new DirtyTriggerProcess($context);
        $list = $process->handle($context);
        return $list;
    }


    /**
     * 获取配置
     */
    public function config()
    {
        $format = [];
        $sources = XssDirtyTrigger::$source;
        foreach ($sources as $value => $label){
            $format['source'][] = ['label' => $label, 'value' => $value];
        }
        return $format;
    }



}
