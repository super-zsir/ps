<?php

namespace Imee\Comp\Nocode\Service\Logic\Model;

use Imee\Comp\Nocode\Models\Cms\NocodeModelConfig;
use Imee\Comp\Nocode\Service\Context\Model\InfoContext;
use Imee\Comp\Nocode\Service\Exception\ModelException;

/**
 * 获取模型详情
 */
class InfoLogic
{
    /**
     * @var InfoContext $context
     */
    private $context;

    /**
     * @var NocodeModelConfig $model
     */
    private $model = NocodeModelConfig::class;

    public function __construct(InfoContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $this->verify();
        $data = $this->model::findOneByWhere([['name', '=', $this->context->name]], 'name,table_config');
        if (empty($data) || empty($data['table_config'])) {
            return [];
        }

        $data = json_decode($data['table_config'], true);
        if (empty($data['fields'])) {
            return [];
        }

        return $data['fields'];
    }

    private function verify(): void
    {
        // 验证资源是否为空
        if (empty($this->context->name)) {
            [$code, $msg] = ModelException::MODEL_NOT_FOUND;
            throw new ModelException($msg, $code);
        }
    }
}