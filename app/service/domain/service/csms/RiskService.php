<?php


namespace Imee\Service\Domain\Service\Csms;

use Imee\Service\Domain\Service\Csms\Context\Risk\AudioProxyContext;
use Imee\Service\Domain\Service\Csms\Context\Risk\TextProxyContext;
use Imee\Service\Domain\Service\Csms\Context\Risk\VideoProxyContext;
use Imee\Service\Domain\Service\Csms\Process\Risk\AudioStrategyProcess;
use Imee\Service\Domain\Service\Csms\Context\Risk\ImageProxyContext;
use Imee\Service\Domain\Service\Csms\Process\Risk\ImageStrategyProcess;
use Imee\Service\Domain\Service\Csms\Process\Risk\TextStrategyProcess;
use Imee\Service\Domain\Service\Csms\Process\Risk\VideoStrategyProcess;

/**
 * 风控
 * Class RiskService
 * @package Imee\Service\Domain\Service\Csms
 */
class RiskService
{
    /**
     * 敏感词
     */
    public function text($params = [])
    {
        $context = new TextProxyContext($params);
        $process = new TextStrategyProcess($context);
        return $process->handle();
    }

    /**
     * 图片
     */
    public function image($params = [])
    {
        $context = new ImageProxyContext($params);
        $process = new ImageStrategyProcess($context);
        return $process->handle();
    }

	/**
	 * 音频检测
	 * @param array $params
	 */
    public function audio($params = [])
    {
        $context = new AudioProxyContext($params);
		$process = new AudioStrategyProcess($context);
		return $process->handle();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function video(array $params = [])
    {
        $context = new VideoProxyContext($params);
        $process = new VideoStrategyProcess($context);
        return $process->handle();
    }
}
