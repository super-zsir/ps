<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Welcomegiftbag\CreateByConditionValidation;
use Imee\Controller\Validation\Operate\Welcomegiftbag\CreateByCrowdValidation;
use Imee\Export\WelcomehuntergiftExport;
use Imee\Service\Helper;
use Imee\Service\Operate\WelcomegiftbagService;
use Imee\Controller\Validation\Operate\Welcomegiftbag\ModifyhunterValidation;
use Imee\Controller\Validation\Operate\Welcomegiftbag\CreatehunterValidation;
use Imee\Controller\Validation\Operate\Welcomegiftbag\DeletehunterValidation;
use Imee\Controller\Validation\Operate\Welcomegiftbag\ImportValidation;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Helper\Traits\ImportTrait;

class WelcomehuntergiftController extends BaseController
{
    use ImportTrait;

    /**
     * @var WelcomegiftbagService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WelcomegiftbagService();
    }

    /**
     * @page welcomehuntergift
     * @name 迎新礼包管理-礼包下发
     */
    public function mainAction()
    {
    }

    /**
     * @page welcomehuntergift
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getHunterList($this->params);

        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page welcomehuntergift
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'welcomehuntergift', model_id = 'id')
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        CreatehunterValidation::make()->validators($params);

        $result = $this->service->createhunter($params);

        return $this->outputSuccess($result);
    }

    /**
     * @page welcomehuntergift
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'welcomehuntergift', model_id = 'id')
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->params);
        ModifyhunterValidation::make()->validators($params);
        $params['deleted'] = 0;
        $result = $this->service->modifyhunter($params);

        return $this->outputSuccess($result);
    }

    /**
     * @page welcomehuntergift
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'welcomehuntergift', model_id = 'id')
     */
    public function deleteAction()
    {
        $params = $this->trimParams($this->params);
        DeletehunterValidation::make()->validators($params);
        $params['deleted'] = 1;
        $result = $this->service->modifyhunter($params);

        return $this->outputSuccess($result);
    }

    /**
     * @page welcomehuntergift
     * @point 模板
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['用户ID', '礼包ID', '有效天数', '礼包总数量'], [], 'welcomehuntergift');
    }

    /**
     * @page welcomehuntergift
     * @point 导入
     */
    public function importAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid', 'gb_id', 'valid_day', 'num']);
        if (!$result || empty($data['data'])) {
            return $this->outputError(-1, $msg ?: '请上传数据');
        }
        if (count($data['data']) > 3000) {
            return $this->outputError(-1, '一次最多导入3000条');
        }
        if ($data['data'][0]['uid'] == '用户ID') {
            unset($data['data'][0]);
            $data['data'] = array_values($data['data']);
        }
        ImportValidation::make()->validators($data);
        $res = $this->service->importBag($data, intval($this->params['admin_uid']));

        return $this->outputSuccess([], ['confirm_message' => $res ? '本次发放的迎新礼包包含了游戏优惠券，请前往批量发放任务记录进行审核' : '']);
    }

    /**
     * @page welcomehuntergift
     * @point 按条件发放
     */
    public function addbyconditionAction()
    {

        CreateByConditionValidation::make()->validators($this->params);

        if (($this->params['c'] ?? '') == 'check') {
            $bags = $this->service->getGiftBagCondition();
            $bags = array_column($bags, 'label', 'value');

            $bigareas = array_column($this->service->getBigareaMap(), 'label', 'value');
            $types = array_column($this->service->getTypeMap(), 'label', 'value');

            $data = [];
            $data[] = '<span style="color:red;font-weight: bold;">你正在操作发放迎新礼包给整个大区的付费用户BD，请仔细确认以下信息准确无误后，再进行操作</span><br/>';
            $data[] = '礼包名称：' . $bags[$this->params['gb_id']] ?? '';
            $data[] = '有效天数：' . $this->params['valid_day'];
            $data[] = '发放大区：' . $bigareas[$this->params['bigarea_id']] ?? '';
            $data[] = '发放类型：' . $types[$this->params['type']] ?? '';
            $data[] = '礼包数量：' . $this->params['num'] ?? '';

            return $this->outputSuccess(['is_confirm' => true, 'confirm_text' => implode('<br/>', $data)]);
        }
        $data = $this->service->addByCondition($this->params);

        return $this->outputSuccess($data);
    }

    /**
     * @page welcomehuntergift
     * @point 按人群发放
     */
    public function addbycrowdAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'import') {
            list($result, $msg, $data) = $this->uploadCsv(['uid']);
            if (!$result || empty($data['data'])) {
                return $this->outputError(-1, $msg ?: '请上传数据');
            }
            $bidString = implode(',', Helper::arrayFilter($data['data'], 'uid'));
            return $this->outputSuccess([
                'url' => $bidString,
                'name' => $bidString,
            ]);
        }
        CreateByCrowdValidation::make()->validators($this->params);
        $data = $this->service->addByCrowd($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page welcomehuntergift
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'welcomehuntergift';
        ExportService::addTask($this->uid, 'welcomehuntergift.xlsx', [WelcomehuntergiftExport::class, 'export'], $this->params, '礼包下发导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}
