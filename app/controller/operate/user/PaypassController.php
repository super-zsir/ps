<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\User\PayPassValidation;
use Imee\Export\Operate\User\PayPassExport;
use Imee\Service\Operate\User\PayPassService;

class PaypassController extends BaseController
{
    /**
     * @var PayPassService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PayPassService();
    }
    
    /**
	 * @page paypass
	 * @name -支付密码管理
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page paypass
	 * @point 列表
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);

        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page paypass
     * @point 重置支付密码
     * @logRecord(content = '重置支付密码', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function resetPayPassAction()
    {
        PayPassValidation::make()->validators($this->params);
        $uid = (int) $this->params['uid'];
        $this->service->reset([$uid], 1);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 重置支付密码次数
     * @logRecord(content = '重置支付密码次数', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function resetPayPassNumAction()
    {
        PayPassValidation::make()->validators($this->params);
        $uid = (int) $this->params['uid'];
        $this->service->reset([$uid], 2);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 重置安全邮箱
     * @logRecord(content = '重置安全邮箱', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function resetEmailAction()
    {
        PayPassValidation::make()->validators($this->params);
        $uid = (int) $this->params['uid'];
        $this->service->reset([$uid], 3);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 修改安全邮箱
     * @logRecord(content = '修改安全邮箱', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function modifyEmailAction()
    {
        PayPassValidation::make()->validators($this->params);
        $email = $this->params['nemail'] ?? '';
        if (empty($email)) {
            $this->outputError(-1, '新邮箱不能为空');
        }
        $this->service->modifyEmail($this->params['uid'], $email);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 批量重置支付密码
     * @logRecord(content = '批量重置支付密码', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function resetPayPassBatchAction()
    {
        PayPassValidation::make()->validators($this->params);
        $this->service->reset($this->params['uid'],1);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 批量重置支付密码次数
     * @logRecord(content = '批量重置支付密码次数', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function resetPayPassNumBatchAction()
    {
        PayPassValidation::make()->validators($this->params);
        $this->service->reset($this->params['uid'], 2);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 批量重置安全邮箱
     * @logRecord(content = '批量重置安全邮箱', action = '1', model = 'paypass', model_id = 'uid')
     */
    public function resetEmailBatchAction()
    {
        PayPassValidation::make()->validators($this->params);
        $this->service->reset($this->params['uid'], 3);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page paypass
     * @point 导出
     */
    public function exportAction()
    {
        $total = $this->service->getCount($this->params);
        if ($total > 50000) {
            exit('最多只能导出5万条记录');
        }
        $this->params['guid'] = 'paypass';
        ExportService::addTask($this->uid, 'paypass.xlsx', [PayPassExport::class, 'export'], $this->params, '支付密码管理导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}