<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;
use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Comp\Nocode\Apijson\Interfaces\QueryInterface;
use Imee\Comp\Nocode\Apijson\Model\MysqlQuery;
use Imee\Comp\Nocode\Apijson\Parse\Handle;

abstract class AbstractHandle
{
    /** @var string 关键字 */
    protected $keyWord;

    /** @var array */
    protected $unsetKey = [];

    /** @var ConditionEntity */
    protected $condition;

    /**
     * @var array 允许在查询值中使用的安全数据库函数白名单
     */
    protected $allowedFunctions = [
        'NOW', 'CURDATE', 'CURTIME', 'UNIX_TIMESTAMP',
        'CONCAT', 'LOWER', 'UPPER', 'LENGTH', 'MD5'
    ];

    public function __construct(ConditionEntity $condition)
    {
        $this->condition = $condition;
    }

    protected function sanitizeKey(string $key): string
    {
        preg_match('#(?<key>[a-zA-z0-9_]+)#', $key, $match);
        return $match['key'] ?? $key;
    }

    public function handle()
    {
        $this->handleBefore();
        $this->buildModel();
        $this->unsetKeySaveCondition();
        $this->handleAfter();
    }

    protected function unsetKeySaveCondition()
    {
        if (empty($this->unsetKey)) return;
        $condition = $this->condition->getCondition();
        foreach ($this->unsetKey as $key) {
            unset($condition[$key]);
        }
        $this->condition->setCondition($condition);
    }

    protected function handleBefore()
    {
    }

    protected function handleAfter()
    {
    }

    protected function subTableQuery(array $data): QueryInterface
    {
        $tableName = $data['from'];
        $tableEntity = new TableEntity($tableName, $data);
        $conditionEntity = $tableEntity->getConditionEntity();
        $conditionEntity->setLimit(0);

        $handle = new Handle($conditionEntity, $tableEntity);
        $handle->buildQuery();

        /** @var QueryInterface $query */
        return new MysqlQuery($tableEntity);
    }

    abstract protected function buildModel();

    /**
     * 解析查询值，如果值是安全的数据库函数，则分离SQL和绑定
     * @param mixed $value
     * @return array [ 'sql' => string, 'bind' => array ]
     */
    protected function parseValue($value): array
    {
        if (is_string($value) && preg_match('/^([a-zA-Z0-9_]+)\((.*)\)$/', $value, $matches)) {
            $functionName = strtoupper($matches[1]);

            if (in_array($functionName, $this->allowedFunctions)) {
                $params = empty($matches[2]) ? [] : array_map('trim', explode(',', $matches[2]));
                $placeholders = implode(', ', array_fill(0, count($params), '?'));

                return [
                    'sql'  => "{$functionName}({$placeholders})",
                    'bind' => $params
                ];
            }
        }

        // 默认情况下，将值作为普通参数处理
        return [
            'sql'  => '?',
            'bind' => [$value]
        ];
    }
}