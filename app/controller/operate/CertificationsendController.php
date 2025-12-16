<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Certification\CertificationAuditValidation;
use Imee\Controller\Validation\Operate\Certification\CertificationSendValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Certification\CertificationSendService;

class CertificationsendController extends BaseController
{
    use ImportTrait;
    /**
     * @var CertificationSendService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CertificationSendService();
    }

    /**
	 * @page certificationsend
	 * @name 运营系统-认证管理-认证发放
	 */
    public function mainAction(){}
    
    /**
	 * @page certificationsend
	 * @point list
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);

        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
	 * @page certificationsend
	 * @point send
	 */
    public function sendAction()
    {
        CertificationSendValidation::make()->validators($this->params);
        list($res, $data) = $this->service->send($this->params);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page certificationsend
     * @point send
     */
    public function sendBatchAction()
    {
        if (!isset($this->params['list']) || empty($this->params['list'])) {
            return $this->outputError(-1, '批量发放失败');
        }
        list($res, $data) = $this->service->sendBatch($this->params);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page certificationsend
     * @point beforeSend
     */
    public function beforeSendAction()
    {
        CertificationSendValidation::make()->validators($this->params);
        $data = $this->service->beforeSend($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page certificationsend
     * @point beforeSendBatch
     */
    public function beforeSendBatchAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid', 'cer_id', 'valid_day', 'content', 'remark']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        foreach ($data['data'] as $item) {
            if (empty($item['uid']) || empty($item['cer_id']) || empty($item['valid_day'])) {
                return $this->outputError(-1, '请检查文件格式，UID、素材ID、有效天数必填');
            }
        }
        $this->params['data'] = $data['data'];
        $data = $this->service->beforeSendBatch($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page certificationsend
     * @point auditBatch
     */
    public function auditBatchAction()
    {
        CertificationAuditValidation::make()->validators($this->params);
        $data = $this->service->audit($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page certificationsend
     * @point template
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['UID', 'Material ID', 'Valid Time', 'Content', 'Remark', '上传前请删除表头'], [], 'template');
    }

    /**
     * @page certificationsend
     * @point getCerId
     */
    public function getCerIdAction()
    {
        $map = $this->service->getCerId();

        return $this->outputSuccess($map);
    }
}