<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Pretty\PrettyUserCustomizeService;
use Imee\Controller\Validation\Operate\Pretty\UserCustomize\ListValidation;
use Imee\Controller\Validation\Operate\Pretty\UserCustomize\CreateValidation;
use Imee\Controller\Validation\Operate\Pretty\UserCustomize\ModifyValidation;
use Imee\Controller\Validation\Operate\Pretty\UserCustomize\ExportValidation;
use Imee\Controller\Validation\Operate\Pretty\UserCustomize\ImportValidation;
use Imee\Export\PrettyUserCustomizeExport;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Helper\Traits\ImportTrait;

class PrettyusercustomizeController extends BaseController
{
    use ImportTrait;
    /**
     * @var PrettyUserCustomizeService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PrettyUserCustomizeService;
    }

    /**
     * @page prettyUserCustomize
     * @name 自选靓号发放管理
     */
    public function mainAction()
    {
    }

    /**
     * @page prettyUserCustomize
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        
        ListValidation::make()->validators($params);
        $res = $this->service->getList($params);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page prettyUserCustomize
     * @point 创建
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        CreateValidation::make()->validators($params);
        $this->service->create($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettyUserCustomize
     * @point 修改
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->params);
        ModifyValidation::make()->validators($params);
        $this->service->modify($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettyUserCustomize
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->trimParams($this->params);
        
        ExportValidation::make()->validators($params);
        $this->params['guid'] = 'prettyUserCustomize';
        ExportService::addTask($this->uid, 'prettyusercustomize.xlsx', [PrettyUserCustomizeExport::class, 'export'], $this->params, '自选靓号发放管理导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

    /**
     * @page prettyUserCustomize
     * @point 模板下载
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['uid','自选靓号类型ID','靓号有效天数','自选资格使用有效天数','是否可转赠（0 不可转赠 1 可转赠 ）','备注', '发放数量'], [], 'import');
    }

    /**
     * @page prettyUserCustomize
     * @point 导入
     */
    public function importAction()
    {
        [$res, $msg, $data] = $this->uploadCsv(['uid', 'customize_pretty_id', 'pretty_validity_day', 'qualification_expire_day','give_type', 'remark', 'send_num']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        ImportValidation::make()->validators($data);
        $this->service->import($data);
    
        return $this->outputSuccess([]);
    }
}
