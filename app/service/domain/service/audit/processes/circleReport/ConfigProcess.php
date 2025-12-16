<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;

use Imee\Models\Xs\XsCircleReport;
use Imee\Service\Helper;

class ConfigProcess
{



    public $reason = [
        '含有涉政信息',
        '含有涉黄信息',
        '含有广告信息',
        '含有违规信息',
        '含有严重涉政信息',
        '含有严重涉黄信息',
        '含有严重广告信息',
        '含有严重违规信息'
    ];



    public function handle()
    {
        $format = [];

        foreach (Helper::getLanguageArr() as $k => $v){
            $format['language'][] = [
                'label' => $v,
                'value' => $k
            ];
        }

        foreach (XsCircleReport::$status as $k => $v) {
            $format['status'][] = [
                'label' => $v,
                'value' => $k
            ];
        }

        foreach (XsCircleReport::$rotype as $k => $v) {
            $format['rotype'][] = [
                'label' => $v,
                'value' => $k
            ];
        }

        foreach ($this->reason as $v){
            $format['reason'][] = [
                'label' => $v,
                'value' => $v
            ];
        }

        return $format;
    }
}
