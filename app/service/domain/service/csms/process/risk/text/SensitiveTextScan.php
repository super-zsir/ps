<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk\Text;

use Imee\Comp\Common\Sdk\SdkFilter;
use Imee\Service\Domain\Service\Csms\Context\Risk\TextProxyContext;
use Imee\Service\Helper;

class SensitiveTextScan
{
    protected $context;
    private $result;

    public function __construct(TextProxyContext $context)
    {
        $this->context = $context;
        $this->result = [
        ];
        $this->init();
    }

    public function init()
    {
        if (!$this->context->path) {
            return false;
        }
        if (!is_array($this->context->path)) {
            $this->context->setParams(array(
                'path' => [$this->context->path]
            ));
        }
        $texts = implode(" ", $this->context->path);
        if (empty($texts)) {
            return false;
        }
        $sensitiveText = $this->checkDirty($texts, 'input');

        if (!empty($sensitiveText)) {
        	$this->result = [
        		'tags' => isset($sensitiveText['type']) ? ['sensitive', $sensitiveText['type']] : [],
		        'reason' => '新敏感词触发:' . $sensitiveText['text']
	        ];
            return true;
        }

        if (preg_match("/(1\d{10})/", $texts, $match)) {
            $this->result = [
		        'tags' => ['sensitive', 'contact'],
		        'reason' => '广告: 包含联系方式'
	        ];
            return true;
        }
        return false;
    }

    /**
     * 新的敏感词检测
     * @param $text, $condition
     * @return string
     */
    private function checkDirty($text, $condition)
    {
        try {
            $filter = new SdkFilter();
            $sensitiveData = $filter->checkDirty($text, 0, $condition); //只查1个，命中即可
            if ($sensitiveData && $sensitiveData['data'] && count($sensitiveData['data']) > 0) {
            	return current($sensitiveData['data']);
            }
        } catch (\Exception $e) {
            Helper::debugger()->error('_checkDirty error -> ' . $e->getMessage() . '###' . $e->getTraceAsString());
        }
        return [];
    }

    public function getResult()
    {
        return $this->result;
    }
}
