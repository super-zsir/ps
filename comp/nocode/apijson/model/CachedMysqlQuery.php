<?php

namespace Imee\Comp\Nocode\Apijson\Model;

use Imee\Comp\Nocode\Apijson\Interfaces\QueryInterface;
use Imee\Comp\Common\Redis\RedisSimple;

class CachedMysqlQuery implements QueryInterface
{
    /**
     * @var QueryInterface
     */
    protected $originalQuery;

    /**
     * @var RedisSimple
     */
    protected $redis;

    /**
     * @var int
     */
    protected $cacheTime;

    const TAG_PREFIX = 'apijson:tag:';
    const DATA_PREFIX = 'apijson:data:';

    public function __construct(QueryInterface $originalQuery, RedisSimple $redis, int $cacheTime = 60)
    {
        $this->originalQuery = $originalQuery;
        $this->redis = $redis;
        $this->cacheTime = $cacheTime;
    }

    private function generateCacheKey(string $type = 'data'): string
    {
        $keyData = [
            'sql' => $this->originalQuery->toSql(),
            'bindings' => $this->originalQuery->getBindings(),
            'type' => $type
        ];
        return self::DATA_PREFIX . md5(json_encode($keyData));
    }

    private function getTagKey(): string
    {
        return self::TAG_PREFIX . $this->originalQuery->getTableName();
    }

    public function all()
    {
        $cacheKey = $this->generateCacheKey('all');
        $result = $this->redis->get($cacheKey);

        if ($result !== false && $result !== null) {
            return $result;
        }

        $result = $this->originalQuery->all();

        if (!empty($result)) {
            $this->redis->setex($cacheKey, $this->cacheTime, $result);
            $this->redis->sAdd($this->getTagKey(), $cacheKey);
        }

        return $result;
    }

    public function count($columns = '*'): int
    {
        $cacheKey = $this->generateCacheKey('count');
        $result = $this->redis->get($cacheKey);

        if ($result !== false && $result !== null) {
            return (int) $result;
        }

        $result = $this->originalQuery->count($columns);

        $this->redis->setex($cacheKey, $this->cacheTime, $result);
        $this->redis->sAdd($this->getTagKey(), $cacheKey);

        return $result;
    }

    public function insert(array $values, $sequence = null): int
    {
        $this->invalidateCache();
        return $this->originalQuery->insert($values, $sequence);
    }

    public function update(array $values): bool
    {
        $this->invalidateCache();
        return $this->originalQuery->update($values);
    }

    public function delete($id = null): bool
    {
        $this->invalidateCache();
        return $this->originalQuery->delete($id);
    }

    protected function invalidateCache(): void
    {
        $tagKey = $this->getTagKey();
        $keys = $this->redis->sMembers($tagKey);

        if (empty($keys)) {
            return;
        }

        // 1. 使用一条 del 命令批量删除所有相关的缓存键
        $this->redis->del($keys);

        // 2. 然后删除标签集合本身
        $this->redis->del($tagKey);
    }

    public function getTableName(): string
    {
        return $this->originalQuery->getTableName();
    }

    public function setPrimaryKey(string $primaryKey): void
    {
        $this->originalQuery->setPrimaryKey($primaryKey);
    }

    public function getPrimaryKey(): string
    {
        return $this->originalQuery->getPrimaryKey();
    }

    public function toSql()
    {
        return $this->originalQuery->toSql();
    }

    public function getBindings()
    {
        return $this->originalQuery->getBindings();
    }

    // 添加缺失的方法
    public function getWhereKeys(): array
    {
        return $this->originalQuery->getWhereKeys();
    }

    public function getRowCount(): int
    {
        return $this->originalQuery->getRowCount();
    }

    public function setColumns(string $columns): void
    {
        $this->originalQuery->setColumns($columns);
    }
} 