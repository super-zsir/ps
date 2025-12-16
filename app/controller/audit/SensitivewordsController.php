<?php

namespace Imee\Controller\Audit;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Audit\SensitiveWords\SensitiveWordsValidation;
use Imee\Export\SensitiveWordsExport;
use Imee\Service\Domain\Service\Audit\SensitiveWordsService;

/**
 * 敏感词记录
 */
class SensitivewordsController extends BaseController
{
    /**
     * @page sensitiveWords
     * @name 审核系统-敏感词记录
     * @point 敏感词记录列表
     */
    public function indexAction()
    {
        SensitiveWordsValidation::make()->validators($this->request->get());
        $service = new SensitiveWordsService();
        $res = $service->list($this->request->get());
		return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page sensitiveWords
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new SensitiveWordsService();
        return $this->outputSuccess($service->getConfig());
    }

	/**
	 * @page sensitiveWords
	 * @point 导出
	 */
	public function exportAction()
	{
		return $this->syncExportWork('sensitiveWordsExport', SensitiveWordsExport::class, $this->request->get());
	}
}
