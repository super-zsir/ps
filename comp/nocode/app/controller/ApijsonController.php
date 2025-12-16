<?php

namespace Imee\Controller\Nocode;

use Imee\Comp\Nocode\Apijson\ApiJson;

class ApijsonController extends AdminBaseController
{
    private $jsonContent = '';
    public function onConstruct()
    {
        parent::onConstruct();
        $this->jsonContent = $this->request->getRawBody();
    }

    /**
     * @page apijson
     * @name 零代码平台
     */
    public function mainAction()
    {
    }

    public function getAction()
    {
        try {
            $apijson = new ApiJson('GET');
            $data = $apijson->Query($this->jsonContent);
            return $this->outputSuccess($data);
        } catch (\Throwable $e) {
            return $this->outputError(-1, $e->getMessage());
        }
    }

    public function postAction()
    {
        try {
            $apijson = new ApiJson('POST');
            $data = $apijson->Query($this->jsonContent);
            return $this->outputSuccess($data);
        } catch (\Throwable $e) {
            return $this->outputError(-1, $e->getMessage());
        }
    }

    public function putAction()
    {
        try {
            $apijson = new ApiJson('PUT');
            $data = $apijson->Query($this->jsonContent);
            return $this->outputSuccess($data);
        } catch (\Throwable $e) {
            return $this->outputError(-1, $e->getMessage());
        }
    }

    public function deleteAction()
    {
        try {
            $apijson = new ApiJson('DELETE');
            $data = $apijson->Query($this->jsonContent);
            return $this->outputSuccess($data);
        } catch (\Throwable $e) {
            return $this->outputError(-1, $e->getMessage());
        }
    }
}