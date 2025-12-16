<?php
namespace Imee\Service\Domain\Service\Csms\Process\Databoard\Forbiddenriskweek;

use Imee\Service\Helper;

class ConfigProcess
{
    public function handle()
    {
        $format = [];
        $format['app_id'][] = [
            'label' => 'All',
            'value' => -2,
        ];
        foreach (Helper::getAllApp() as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['app_id'][] = $tmp;
        }
        return $format;
    }
}
