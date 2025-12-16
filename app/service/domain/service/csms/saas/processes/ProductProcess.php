<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Models\Xss\CsmsChoiceField;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Models\Xss\CsmsProduct;
use Imee\Models\Xss\CsmsServicerScene;
use Imee\Service\Domain\Service\Csms\Context\Saas\ProductContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ProductOperateContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ProductException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 产品
 */
class ProductProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 产品列表
     * @param ProductContext $context
     * @return array
     */
    public function productList(ProductContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id',  'app_id', 'name', 'state'];
        $list = CsmsProduct::handleList($condition);
        $total = $this->productTotal($condition);
        if ($list) {
            foreach ($list as &$v) {
                $v['state_name'] = CsmsProduct::state[$v['state']] ?? '';
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function productTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsProduct::handleTotal($condition);
    }

    /**
     * @param ProductContext $context
     * @return array
     */
    private function filterConditions(ProductContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
//            'fid' => $context->fid,
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param ProductOperateContext $context
     * @return bool
     */
    public function addProduct(ProductOperateContext $context): bool
    {
        $one = CsmsProduct::handleOne(array(
            'app_id' => $context->appId,
        ));
        if ($one) {
            ProductException::throwException(ProductException::PRODUCT_ALLREADY_EXIST);
        }
        $conditon = array(
            'app_id' => $context->appId,
            'name' => $context->name,
            'state' => $context->state
        );
        $res = CsmsProduct::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addProduct,
                'arm_id' => CsmsProduct::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 更新
     * @param ProductOperateContext $context
     * @return bool
     */
    public function updateProduct(ProductOperateContext $context): bool
    {
        $one = CsmsProduct::handleOne(array(
            'app_id' => $context->appId,
        ));
        if ($one && $context->id != $one->id) {
            ProductException::throwException(ProductException::PRODUCT_ALLREADY_EXIST);
        }
        $conditon = array(
            'app_id' => $context->appId,
            'name' => $context->name,
            'state' => $context->state
        );
        $res = CsmsProduct::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editProduct, // 更新
                'arm_id' => $context->id,
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 删除
     * @param ProductOperateContext $context
     * @return bool
     */
    public function deleteProduct(ProductOperateContext $context)
    {
        return CsmsProduct::handleDelete($context->id);
    }

    /**
     * @return array
     */
    public function products()
    {
        $list = CsmsProduct::handleList(array(
            'columns' => ['name', 'app_id'],
            'state' => CsmsProduct::STATE_NORMAL
        ));
        $res = [];
        if ($list) {
            foreach ($list as $item) {
                $res[] = array(
                    'label' => $item['name'],
                    'value' => $item['app_id'],
                );
            }
        }
        return $res;
    }
}
