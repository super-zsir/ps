<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\MultilangCreateValidation;
use Imee\Controller\Validation\Operate\MultilangBatchCreateValidation;
use Imee\Controller\Validation\Operate\MultilangBatchUpdateValidation;
use Imee\Controller\Validation\Operate\MultilangModifyValidation;
use Imee\Service\Operate\Multilang\MultilangService;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Helper\Traits\ImportTrait;

class MultilangController extends BaseController
{
    use ImportTrait;
    /**
     * @var MultilangService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new MultilangService();
    }

    /**
     * @page multilang
     * @name APP多语言配置
     */
    public function mainAction()
    {
    }

    /**
     * @page multilang
     * @name APP多语言配置
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        $res = $this->service->getList($params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @page multilang
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'multilang', model_id = 'id')
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        MultilangCreateValidation::make()->validators($params);
        $res = $this->service->create($params);
        return $this->outputSuccess($res);
    }

    /**
     * @page multilang
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'multilang', model_id = 'id')
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->params);
        MultilangModifyValidation::make()->validators($params);
        $data = $this->service->modify($params);
        return $this->outputSuccess($data);
    }

    /**
     * @page multilang
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'multilang', model_id = 'id')
     */
    public function deleteAction()
    {
        $params = $this->trimParams($this->params);
        $data = $this->service->delete($params);
        return $this->outputSuccess($data);
    }

    /**
     * @page multilang
     * @point 发布
     * @logRecord(content = '发布', action = '1', model = 'multilang', model_id = 'id')
     */
    public function publishAction()
    {
        $this->service->publish();
        return $this->outputSuccess(['id' => 0, 'after_json' => []]);
    }

    /**
     * @page multilang
     * @point 批量创建
     * @logRecord(content = '批量创建', action = '0', model = 'multilang', model_id = 'id')
     */
    public function importAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            $upload = ['key', 'version', 'group(1-客户端 2-服务端 3-web端 0-3端公用)', 'zh_cn', 'zh_tw', 'en', 'ko', 'ms', 'th', 'id', 'vi', 'ar', 'tr', 'hi', 'ur', 'bn', 'tl'];
            (new Csv())->exportToCsv($upload, [], 'multilangBatchCreate');
            exit;
        }
        $upload = ['key', 'version', 'group', 'zh_cn', 'cn', 'en', 'ko', 'ms', 'th', 'id', 'vi', 'ar', 'tr', 'hi', 'ur', 'bn', 'tl'];
        [$success, $msg, $data] = $this->uploadCsv($upload);

        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        if (empty($data['data'])) {
            return $this->outputError('-1', '请填写数据后再上传！');
        }
        $this->service->import($data['data']);
        return $this->outputSuccess(['id' => 0, 'after_json' => []]);
    }

    /**
     * @page multilang
     * @point 批量修改
     * @logRecord(content = '批量修改', action = '1', model = 'multilang', model_id = 'id')
     */
    public function batchUpdateAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            $upload = ['key', 'version', 'group(1-客户端 2-服务端 3-web端 0-3端公用)', 'zh_cn', 'zh_tw', 'en', 'ko', 'ms', 'th', 'id', 'vi', 'ar', 'tr', 'hi', 'ur', 'bn', 'tl'];
            (new Csv())->exportToCsv($upload, [], 'multilangBatchUpdate');
            exit;
        }
        $upload = ['key', 'version', 'group', 'zh_cn', 'cn', 'en', 'ko', 'ms', 'th', 'id', 'vi', 'ar', 'tr', 'hi', 'ur', 'bn', 'tl'];
        [$success, $msg, $data] = $this->uploadCsv($upload);
        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        if (empty($data['data'])) {
            return $this->outputError('-1', '请填写数据后再上传！');
        }
        $this->service->batchUpdate(['list' => $data['data']]);
        return $this->outputSuccess(['id' => 0, 'after_json' => []]);
    }
} 