<?php

namespace Imee\Controller\Operate\Minicard;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Minicard\MiniCardSendValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsItemCardLog;
use Imee\Service\Operate\Minicard\MiniCardSendService;

class MinicardsendController extends BaseController
{
    use ImportTrait;

    /** @var MiniCardSendService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->params['type'] = XsItemCard::TYPE_MINI;
        $this->service = new MiniCardSendService();
    }
    
    /**
     * @page minicardsend
     * @name mini卡装扮下发
     */
    public function mainAction()
    {
    }
    
    /**
     * @page minicardsend
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page minicardsend
     * @point 下发
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        MiniCardSendValidation::make()->validators($params);

        [$state, $errors] = $this->service->create($params);
        if ($state) {
            return $this->outputSuccess();
        }
        return $this->outputSuccess(['is_confirm' => true, 'confirm_text' => implode('<br/>', $errors), 'width' => 700]);
    }
    
    /**
     * @page minicardsend
     * @point 批量下发
     */
    public function importAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsItemCardLog::$uploadFields), [], 'minicardImport');
            exit;
        }

        [$success, $msg, $data] = $this->uploadCsv(array_keys(XsItemCardLog::$uploadFields));
        if (!$success) {
            return $this->outputError('-1', $msg);
        }

        if (count($data['data']) > 5000) {
            return $this->outputError(-1, '一次导入最多5000条');
        }

        $this->service->import($data['data'], $this->params['type']);
        return $this->outputSuccess();
    }
}