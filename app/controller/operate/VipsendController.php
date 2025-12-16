<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\VipsendExport;
use Imee\Service\Forbidden\LoginInfoExport;
use Imee\Service\Helper;
use Imee\Service\Operate\VipsendService;
use Imee\Controller\Validation\Operate\Viprecord\ListValidation;
use Imee\Controller\Validation\Operate\Viprecord\CreateValidation;
use Imee\Controller\Validation\Operate\Viprecord\RetryValidation;
use Imee\Controller\Validation\Operate\Viprecord\ImportValidation;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Helper\Traits\ImportTrait;

class VipsendController extends BaseController
{
    use ImportTrait;
    /**
     * @var VipsendService
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VipsendService();
    }

    /**
     * @page vipsend
     * @name VIP发放
     */
    public function mainAction()
    {
    }

    /**
     * @page vipsend
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        ListValidation::make()->validators($params);
        $result = $this->service->getList($params);
        return $this->outputSuccess($result['data'] ?? [], array('total' => $result['total'] ?? 0));
    }

    /**
     * @page vipsend
     * @point 创建
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        CreateValidation::make()->validators($params);

        $c = $params['c'] ?? '';
        if ($c == 'check') {
            $result = $this->service->checkCreate($params);

            if (isset($result['is_info']) && $result['is_info'] === true) {
                return $this->outputSuccess(['is_info' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            } elseif (isset($result['is_confirm']) && $result['is_confirm'] === true) {
                return $this->outputSuccess(['is_confirm' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            }

            return $this->outputSuccess(['is_confirm' => false]);
        }

        $this->service->create($params);
        return $this->outputSuccess();
    }

    /**
     * @page vipsend
     * @point 重试
     */
    public function retryAction()
    {
        $params = $this->trimParams($this->params);
        RetryValidation::make()->validators($params);
        $this->service->retry($params);
        return $this->outputSuccess();
    }
    /**
     * @page vipsend
     * @point 导入
     */
    public function importAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid', 'vip_level', 'vip_day', 'type','send_num', 'remark']);
        if (!$result || empty($data['data'])) {
            return $this->outputError(-1, $msg ?: '导入数据为空');
        }
        
        ImportValidation::make()->validators($data);

        $c = $this->params['c'] ?? '';
        if ($c == 'check') {
            $result = $this->service->checkImport($data['data']);

            if (isset($result['is_info']) && $result['is_info'] === true) {
                return $this->outputSuccess(['is_info' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            } elseif (isset($result['is_confirm']) && $result['is_confirm'] === true) {
                return $this->outputSuccess(['is_confirm' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            }

            return $this->outputSuccess(['is_confirm' => false]);
        }

        $this->service->import($data);
    
        return $this->outputSuccess([]);
    }

    /**
     * @page vipsend
     * @point 模板
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['uid', 'VIP等级', 'VIP天数','是否可转赠(0 直接生效 1 用户手动生效可转赠 2 用户手动生效不可转赠)','发放数量', '备注' ], [], 'vipsend');
    }

    /**
     * @page vipsend
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'vipsend';

        $params = $this->trimParams($this->params);
        if (empty($params['op_uid']) && empty($params['dateline_sdate']) && empty($params['dateline_edate']) && empty($params['uid']) && empty($params['vip_level'])) {
            return $this->outputError(-1, '请至少筛选一项后再进行导出：创建人、创建时间、用户ID、VIP等级');
        }

        ExportService::addTask($this->uid, 'vipsend.xlsx', [VipsendExport::class, 'export'], $params, 'VIP发放导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

    /**
     * @page vipsend
     * @point VIP7及以上
     */
    public function vip7Action()
    {
        return $this->outputSuccess();
    }
    
    /**
     * @page vipsend
     * @point 绕过RPC接口检查
     */
    public function nocheckAction()
    {
        return $this->outputSuccess();
    }
}
