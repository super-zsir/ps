<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Comp\Nocode\Apijson\Interfaces\QueryInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Imee\Comp\Nocode\Apijson\Model\MysqlQuery;
use Imee\Comp\Nocode\Apijson\Model\CachedMysqlQuery;
use Imee\Comp\Common\Redis\RedisSimple;
use Imee\Comp\Common\Redis\RedisBase;

abstract class AbstractMethod
{
    /** @var QueryInterface $query */
    protected $query;

    /** @var bool $isQueryMany */
    protected $isQueryMany = false;

    /** @var bool $arrayQuery */
    protected $arrayQuery = false;

    /** @var TableEntity */
    protected $tableEntity;

    protected $method;

    public function __construct(TableEntity $tableEntity, string $method = 'GET')
    {
        $this->tableEntity = $tableEntity;
        $this->method = $method;

        $this->buildQuery();
        $this->isQueryMany = substr($this->tableEntity->getTableName(), -2) === '[]';
    }

    public function handle(): ?array
    {
        if (!$this->validateCondition()) return null;
        
        // 检查操作权限
        $this->tableEntity->checkPermission($this->method);
        
        return $this->process();
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    protected function buildQuery()
    {
        // 1. 创建原始的Query实例
        $mysqlQuery = new MysqlQuery($this->tableEntity);

        // 2. 检查请求中是否包含缓存指令，例如 "@cache": 300 (缓存300秒)
        $cacheTime = $this->tableEntity->getConditionEntity()->getCondition()['@cache'] ?? 0;

        if ($cacheTime > 0 && $this->method === 'GET') { // 只对GET请求启用缓存
            // 3. 直接实例化 Redis 服务，不再使用 DI 容器
            $redisService = new RedisSimple(RedisBase::REDIS_ADMIN);
            $this->query = new CachedMysqlQuery($mysqlQuery, $redisService, (int)$cacheTime);
        } else {
            // 4. 否则，直接使用原始实例
            $this->query = $mysqlQuery;
        }
    }

    protected function parseManyResponse(array $ids, bool $isQueryMany = false): array
    {
        if ($isQueryMany) {
            $response = [
                'id[]' => $ids,
                'count' => count($ids)
            ];
        } else {
            $response['id'] = current($ids) ?: 0;
        }
        return $response;
    }

    public function setQueryMany(bool $isQueryMany = false)
    {
        $this->isQueryMany = $isQueryMany;
    }

    public function setArrayQuery(bool $arrayQuery = false)
    {
        $this->arrayQuery = $arrayQuery;
    }

    protected function isQueryMany(): bool
    {
        return $this->isQueryMany; //可能有不止一个因素影响
    }

    abstract protected function validateCondition(): bool;

    abstract protected function process();
}