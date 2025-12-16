<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Sensitive;

use Imee\Comp\Common\Sdk\SdkFilter;
use Imee\Service\Helper;

/**
 * 敏感词配置
 */
class ConfigProcess
{
    public function handle()
    {
        $filter = new SdkFilter();

        $data = [
            'type_list' => [],
            'cond_list' => [],
            'sub_type' => [],
        ];
        $result = $filter->dirtySearchCond();
        if (isset($result['data']) && $result['data']) {
            foreach ($result['data']['type_list'] as $key => $value) {
                $tmp['label'] = $value['label'];
                $tmp['value'] = $value['value'];
                $data['type_list'][] = $tmp;
                if (isset($value['sub']) && !empty($value['sub'])) {
                    foreach ($value['sub'] as $sub_type) {
                        $tmp['label'] = $sub_type['label'];
                        $tmp['value'] = $sub_type['value'];
                        $data['sub_type'][$value['value']][] = $tmp;
                    }
                }
            }
            foreach ($result['data']['cond_list'] as $key => $value) {
                $tmp['label'] = $value['label'];
                $tmp['value'] = $value['value'];
                $data['cond_list'][] = $tmp;
            }
        }

        foreach (Helper::getLanguageArr() as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $data['language'][] = $tmp;
        }

        foreach (CommonConst::$danger as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = (string)$key;
            $data['danger'][] = $tmp;
        }

        foreach (CommonConst::$dirtyTextDeleted as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = (string)$key;
            $data['deleted'][] = $tmp;
        }

        foreach (CommonConst::$vague as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = (string)$key;
            $data['vague'][] = $tmp;
        }

        foreach (CommonConst::$displayAccurate as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = (string)$key;
            $data['accurate'][] = $tmp;
        }
        return $data;
    }
}
