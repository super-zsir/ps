<?php

namespace Imee\Service\Domain\Service\Csms\Saas;

use Imee\Service\Domain\Service\Csms\Exception\Saas\BaseException;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ProductException;
use Imee\Service\Domain\Service\Csms\Context\Saas\ProductContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ProductOperateContext;
use Imee\Service\Domain\Service\Csms\Saas\Processes\ProductProcess;

/**
 * 产品配置服务
 */
class ProductService extends BaseService
{
    /**
     * @var ProductContext
     */
    private $productContext;

    /**
     * @var ProductOperateContext
     */
    private $productOperateContext;

    public function initProductContext(array $conditions)
    {
        $this->productContext = new ProductContext($conditions);
    }

    public function initProductOperateContext(array $conditions)
    {
        $this->productOperateContext = new ProductOperateContext($conditions);
    }

    /**
     * 产品列表
     * @return array
     * @throws \ReflectionException
     */
    public function productList(): array
    {
        try {
            $process = new ProductProcess();
            return $process->productList($this->productContext);
        } catch (\Exception $e) {
            ProductException::throwException(ProductException::PRODUCT_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新产品
     * @return bool
     * @throws \ReflectionException
     */
    public function operateProduct(): bool
    {
        try {
            $process = new ProductProcess();
            if ($this->productOperateContext->id > 0) {
                // 更新
                return $process->updateProduct($this->productOperateContext);
            }
            // 新建
            return $process->addProduct($this->productOperateContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            ProductException::throwException(ProductException::PRODUCT_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * @return array|void
     * @throws \ReflectionException
     */
    public function products()
    {
        try {
            $process = new ProductProcess();
            return $process->products();
        } catch (\Exception $e) {
            ProductException::throwException(ProductException::PRODUCT_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }
}