<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\UserCustomize;

use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsUserCustomizePretty;

/**
 * 列表
 */
class ConfigProcess
{
    public function getStyleAll()
    {
        $format = [];

        $models = XsCustomizePrettyStyle::find([
            'order' => 'id desc'
        ]);
        foreach ($models as $k => $model) {
            $tmp['label'] = $model->id . ' - ' . $model->name;
            $tmp['value'] = (string)$model->id;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getStatus()
    {
        $format = [];

        foreach (XsUserCustomizePretty::$displayStatus as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = (string)$k;
            $format[] = $tmp;
        }
        return $format;
    }
}
