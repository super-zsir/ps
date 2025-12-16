<?php
/**
 * 内部调用响应格式
 */

namespace Imee\Helper\Traits;

trait ResponseTrait
{
    protected function outputJson($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function outputSuccess($data = null, $options = null)
    {
        $array = array(
            'success' => true,
            'code' => 0,
            'data' => $data
        );
        if ($options) {
            $array = array_merge($array, $options);
        }
        return $this->outputJson($array);
    }

    protected function outputError($code, $msg = null, $options = null)
    {
        $array = array(
            'success' => false,
            'code' => $code,
            'msg' => $msg
        );
        if ($options) {
            $array = array_merge($array, $options);
        }
        return $this->outputJson($array);
    }
}
