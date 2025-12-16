<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Style;

use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Service\StatusService;

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

    public function getStyleInUse()
    {
        $data = XsCustomizePrettyStyle::getListByWhere([['disabled', '=', XsCustomizePrettyStyle::DISABLED_NO]], 'id,name', 'id desc');

        foreach ($data as &$row) {
            $row['name'] = $row['id'] . ' - ' . $row['name'];
        }
        
        return StatusService::formatMap(array_column($data, 'name', 'id'));
    }

    
}
