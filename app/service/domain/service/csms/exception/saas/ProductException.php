<?php


namespace Imee\Service\Domain\Service\Csms\Exception\Saas;

class ProductException extends BaseException
{
    protected $serviceCode = '16';

    const PRODUCT_LIST_ERROR = ['01', '加载服务商列表错误'];
    const PRODUCT_EDIT_ERROR = ['02', '服务商新增或修改错误'];
    const PRODUCT_ALLREADY_EXIST = ['03', 'appid已存在'];
}
