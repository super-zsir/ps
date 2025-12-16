<?php

namespace Imee\Comp\Common\Ots;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\GetItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Imee\Comp\Common\Log\LoggerProxy;
use Config\ConfigAwsDynamoDb;
use Imee\Exception\ApiException;

class DynamoDbBase
{
    private $_tableName;
    public $_client;

    const SCHEMA = '';//配置库名D
    const TABLE_NAME = '';//可选，自定义表名

    //https://docs.aws.amazon.com/general/latest/gr/ddb.html
    //https://async-aws.com/clients/dynamodb.html
    public function __construct()
    {
        $this->_tableName = $this->getTableName();
        $this->_client = $this->getClient();
    }

    /**
     * @param array $pk $pk =[['uid', $tpuid], ['topic_id', $tpid]];
     * @params string $condition attribute_exists(attribute1)
     * @return bool
     */
    public function delete(array $pk, $condition = null)
    {
        $request = [
            'TableName'    => $this->_tableName,
            'Key'          => $this->packKey($pk),
            'ReturnValues' => 'NONE'
        ];

        if ($condition) {
            $request['ConditionExpression'] = $condition;
        }

        try {
            $this->_client->deleteItem($request);
            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * @param array $pk $pk =[['uid', $tpuid], ['topic_id', $tpid]];
     * @params array $update [['uid', $tpuid], ['topic_id', $tpid]];
     * @return bool
     */
    public function update(array $pk, array $update)
    {
        $updateExpression = 'SET ';
        $expressionAttributeValues = [];
        foreach ($update as $index => $item) {
            $attributeName = ':val' . ($index + 1);
            $updateExpression .= "{$item[0]} = {$attributeName}, ";
            $expressionAttributeValues[$attributeName] = $this->createAttributeValue($item[1]);
        }
        // 移除末尾的逗号和空格
        $updateExpression = rtrim($updateExpression, ', ');

        $request = [
            'TableName'                 => $this->_tableName,
            'Key'                       => $this->packKey($pk),
            'UpdateExpression'          => $updateExpression,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ReturnValues'              => 'NONE' // 可选，设置返回值的选项，如 `NONE`, `ALL_OLD`, `UPDATED_OLD`, `ALL_NEW`, `UPDATED_NEW`
        ];

        try {
            $this->_client->updateItem($request);
            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    public function getRange(array $pkStart, array $pkEnd, string $sort = 'DESC', array $expression = [], $limit = 100)
    {
        $request = [
            'TableName'              => $this->_tableName,
            'Limit'                  => $limit,
            'ReturnConsumedCapacity' => 'TOTAL',
            'ScanIndexForward'       => $sort === 'ASC' // 设置为 true 表示升序排列，设置为 false 表示降序排列
        ];

        // 设置扫描范围的起始和结束主键
        $request['ScanFilter'] = [
            'range_key' => [
                'AttributeValueList' => [$this->packKey($pkStart), $this->packKey($pkEnd)],
                'ComparisonOperator' => 'BETWEEN'
            ]
        ];

        // 添加其他的扫描参数
        if (!empty($expression)) {
            $request = array_merge($request, $expression);
        }

        return $this->query($request);
    }

    public function query(array $request)
    {
        try {
            if (!isset($request['TableName'])) {
                $request['TableName'] = $this->_tableName;
            }

            //转换值
            if (!empty($request['ExpressionAttributeValues'])) {
                foreach ($request['ExpressionAttributeValues'] as &$value) {
                    $value = $this->createAttributeValue($value);
                }
            }
            if (empty($request['FilterExpression']) && isset($request['FilterExpression'])) {
                unset($request['FilterExpression']);
            }

            $total = $this->getTotalRecordCount($request);
            if (!$total) {
                return [];
            }

            $data = [];
            $response = $this->_client->query($request);
            $items = $response->getItems(true);
            foreach ($items as $item) {
                $v = [];
                foreach ($item as $attributeName => $value) {
                    $v[$attributeName] = $this->extractValue($value);
                }
                $data[] = $v;
            }

            return [
                'data'       => $data,
                'total'      => $total,
                'next_token' => $this->convertLastEvaluatedKey($response->getLastEvaluatedKey())
            ];
        } catch (\Exception $e) {
            throw new ApiException(ApiException::MSG_ERROR, $e->getMessage());
            //$this->logError($e);
            //return [];
        }
    }

    /**
     * 获取总记录数
     *
     * @param array $request 查询请求参数
     * @return int 总记录数
     */
    public function getTotalRecordCount(array $request): int
    {
        if (!empty($request['Limit'])) {
            unset($request['Limit']);
        }
        $request['Select'] = 'COUNT';
        return $this->_client->query($request)->getCount();
    }

    /**
     * 将 AttributeValue 对象转换为 DynamoDB 可用的格式
     *
     * @param array $lastEvaluatedKey 包含 AttributeValue 对象的数组
     * @return array 转换后的数组
     */
    private function convertLastEvaluatedKey(array $lastEvaluatedKey): array
    {
        $convertedKey = [];
        foreach ($lastEvaluatedKey as $key => $value) {
            $convertedKey[$key] = $this->convertAttributeValue($value);
        }
        return $convertedKey;
    }

    /**
     * 将单个 AttributeValue 对象转换为 DynamoDB 可用的格式
     *
     * @param AttributeValue $attributeValue
     * @return array 转换后的值
     */
    private function convertAttributeValue(AttributeValue $attributeValue): array
    {
        if (null !== $attributeValue->getS()) {
            return ['S' => $attributeValue->getS()];
        }
        if (null !== $attributeValue->getN()) {
            return ['N' => $attributeValue->getN()];
        }
        if (null !== $attributeValue->getB()) {
            return ['B' => $attributeValue->getB()];
        }
        if (null !== $attributeValue->getSS()) {
            return ['SS' => $attributeValue->getSS()];
        }
        if (null !== $attributeValue->getNS()) {
            return ['NS' => $attributeValue->getNS()];
        }
        if (null !== $attributeValue->getBS()) {
            return ['BS' => $attributeValue->getBS()];
        }
        if (null !== $attributeValue->getM()) {
            return ['M' => array_map([self::class, 'convertAttributeValue'], $attributeValue->getM())];
        }
        if (null !== $attributeValue->getL()) {
            return ['L' => array_map([self::class, 'convertAttributeValue'], $attributeValue->getL())];
        }
        if (null !== $attributeValue->getNULL()) {
            return ['NULL' => $attributeValue->getNULL()];
        }
        if (null !== $attributeValue->getBOOL()) {
            return ['BOOL' => $attributeValue->getBOOL()];
        }

        throw new \InvalidArgumentException('Unsupported attribute value');
    }

    /**
     * @param array $pk $pk =[['uid', $tpuid], ['topic_id', $tpid]];
     * @return array
     */
    public function getRow(array $pk)
    {
        try {
            $response = $this->_client->getItem(new GetItemInput([
                'TableName'      => $this->_tableName,
                'ConsistentRead' => true,
                'Key'            => $this->packKey($pk),
            ]));

            $data = [];
            $item = $response->getItem();
            foreach ($item as $attributeName => $value) {
                $data[$attributeName] = $this->extractValue($value);
            }
            return $data;
        } catch (\Exception $e) {
            $this->logError($e);
            return [];
        }
    }

    /**
     * @param array $pks
     *         $pks = [
     *            [['id', 123], ['name', 'John']],
     *            [['id', 456], ['name', 'Jane']],
     *        ];
     * @return array
     */
    public function getRows(array $pks)
    {
        $keys = [];
        foreach ($pks as $pk) {
            $keys[] = $this->packKey($pk);
        }

        try {
            $response = $this->_client->batchGetItem([
                'RequestItems'           => [
                    $this->_tableName => [
                        'Keys' => $keys
                    ]
                ],
                'ReturnConsumedCapacity' => 'TOTAL'
            ]);
            $data = [];
            $tableItems = $response->getResponses()[$this->_tableName];
            foreach ($tableItems as $item) {
                $v = [];
                foreach ($item as $attributeName => $value) {
                    $v[$attributeName] = $this->extractValue($value);
                }
                $data[] = $v;
            }
            return $data;
        } catch (\Exception $e) {
            $this->logError($e);
            return [];
        }
    }

    private function extractValue($value)
    {
        $value = $value->requestBody();
        // 使用适当的逻辑从 Value 构建属性值
        if (isset($value['N'])) {
            return $value['N'];
        } elseif (isset($value['S'])) {
            return $value['S'];
        } elseif (isset($value['BOOL'])) {
            return $value['BOOL'];
        } elseif (isset($value['M'])) {
            $result = [];
            foreach ($value['M'] as $key => $subValue) {
                $result[$key] = $this->extractValue($subValue);
            }
            return $result;
        } elseif (isset($value['L'])) {
            $result = [];
            foreach ($value['L'] as $subValue) {
                $result[] = $this->extractValue($subValue);
            }
            return $result;
        } elseif (isset($value['B'])) {
            // 处理二进制类型
            return $value['B'];
        } elseif (isset($value['BS'])) {
            // 处理二进制集合类型
            return $value['BS'];
        } elseif (isset($value['SS'])) {
            // 处理字符串集合类型
            return $value['SS'];
        } elseif (isset($value['NULL'])) {
            // 处理 NULL 类型
            return null;
        }

        return null;
    }

    private function packKey($pk)
    {
        $keyArr = [];
        foreach ($pk as $v) {
            $keyArr[$v[0]] = $this->createAttributeValue($v[1]);
        }
        return $keyArr;
    }

    private function createAttributeValue($value)
    {
        if (is_string($value)) {
            return ['S' => $value];
        } elseif (is_numeric($value)) {
            return ['N' => strval($value)];
        } elseif (is_bool($value)) {
            return ['BOOL' => $value];
        } elseif (is_array($value) && isset($value[0])) {
            // 索引数组
            $stringArray = array_map('strval', $value);
            return ['SS' => array_values($stringArray)];
        } elseif (is_array($value) && !isset($value[0])) {
            // 关联数组 map
            $mapValue = [];
            foreach ($value as $key => $subValue) {
                $mapValue[$key] = $this->createAttributeValue($subValue);
            }
            return ['M' => $mapValue];
        } elseif (is_null($value)) {
            // 支持 NULL 类型 NULL
            return ['NULL' => true];
        }

        // 如果有其他类型需要支持，请根据实际需求进行扩展

        return null;
    }

    private function getTableName()
    {
        $className = static::class;
        $class = new \ReflectionClass($className);
        if ($class->hasConstant('TABLE_NAME') && static::TABLE_NAME) {
            return static::TABLE_NAME;
        }
        $className = basename(str_replace('\\', '/', $className));
        if (substr($className, 0, 3) != 'OTS') {
            throw new \Exception("className must start with OTS");
        }
        $name = preg_replace_callback("/[A-Z]/", function ($match) {
            return '_' . strtolower($match[0]);
        }, substr($className, 3));
        return substr($name, 1);
    }

    /**
     * instanceName + AccessKeyID组合
     * 示例：
     * const XsFriendCircleAccessKeyID
     * @return array
     */
    private function getAkSk()
    {
        $key = static::SCHEMA;
        $key = str_replace(' ', '', ucwords(str_replace('-', ' ', $key)));
        $id = $key . 'AccessKeyID';
        $secret = $key . 'AccessKeySecret';

        $class = new \ReflectionClass('Config\ConfigAwsDynamoDb');
        if ($class->hasConstant($id) && $class->hasConstant($secret)) {
            return [$class->getConstant($id), $class->getConstant($secret)];
        }

        exit('请配置ak/sk:' . $id . '/' . $secret);
    }

    private function getClient()
    {
        if ($this->_client !== null) {
            return $this->_client;
        }

        [$ak, $sk] = $this->getAkSk();

        $region = ENV === 'dev' ? ConfigAwsDynamoDb::RegionDev : ConfigAwsDynamoDb::Region;
        $config = [
            'accessKeyId'     => $ak,
            'accessKeySecret' => $sk,
            'region'          => $region,
        ];

        //如果有配置EndPint就加上
        $endPoint = ENV === 'dev' ? ConfigAwsDynamoDb::EndPointDev : ConfigAwsDynamoDb::EndPoint;
        if (defined($endPoint)) {
            $config['endpoint'] = constant($endPoint);
        }

        return new DynamoDbClient($config);
    }

    protected function logError(\Exception $e)
    {
        LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
    }

    //KeyConditionExpression只支持下面判断符号
    //= 等于
    //< 小于
    //<= 小于等于
    //> 大于
    //>= 大于等于
    //BETWEEN 在两个值之间
    //BEGINS_WITH 以指定前缀开头
    protected function buildKeyConditionExpression(array $conditions): string
    {
        $expressions = [];
        foreach ($conditions as $condition) {
            $fieldName = '#' . $condition['fieldName'];
            $fieldValue = ':' . $condition['fieldName'];
            switch ($condition['operator']) {
                case 'EQ':
                    $expression = "{$fieldName} = {$fieldValue}";
                    break;
                case 'BETWEEN':
                    $expression = "{$fieldName} BETWEEN {$fieldValue}.from AND {$fieldValue}.to";
                    break;
                case 'BEGINS_WITH':
                    $expression = "begins_with({$fieldName}, {$fieldValue})";
                    break;
                case '=':
                case '<':
                case '<=':
                case '>':
                case '>=':
                    $expression = "{$fieldName} {$condition['operator']} {$fieldValue}";
                    break;
                default:
                    throw new ApiException(ApiException::MSG_ERROR, 'KeyConditionExpression not allow operator:' . $condition['operator']);
            }

            $expressions[] = $expression;
        }

        if (!empty($expressions)) {
            return implode(' AND ', $expressions);
        }

        return '';
    }

    protected function buildFilterExpression(array $conditions): string
    {
        $expressions = [];
        foreach ($conditions as $condition) {
            $fieldName = '#' . $condition['fieldName'];
            $fieldValue = ':' . $condition['fieldName'];
            switch ($condition['operator']) {
                case 'EQ':
                    $expression = "{$fieldName} = {$fieldValue}";
                    break;
                case 'CONTAINS':
                    $expression = "contains({$fieldName}, {$fieldValue})";
                    break;
                case 'BETWEEN':
                    $expression = "{$fieldName} BETWEEN {$fieldValue}.from AND {$fieldValue}.to";
                    break;
                case 'IN':
                    $attributeValuePlaceholders = [];
                    foreach ($condition['fieldValue'] as $key => $item) {
                        $attributeValuePlaceholders[] = ":{$condition['fieldName']}_{$key}";
                    }
                    $fieldValue = implode(', ', $attributeValuePlaceholders);
                    $expression = "{$fieldName} IN ({$fieldValue})";
                    break;
                case 'BEGINS_WITH':
                    $expression = "begins_with({$fieldName}, {$fieldValue})";
                    break;
                default:
                    $expression = "{$fieldName} {$condition['operator']} {$fieldValue}";
                    break;
            }

            $expressions[] = $expression;
        }

        if (!empty($expressions)) {
            return implode(' AND ', $expressions);
        }

        return '';
    }

    protected function buildExpressionAttributeNames(array $keyConditions, array $filterConditions = []): array
    {
        $conditions = array_merge($keyConditions, $filterConditions);

        $names = [];
        foreach ($conditions as $condition) {
            $fieldName = $condition['fieldName'];
            $names["#{$fieldName}"] = $fieldName;
        }

        return $names;
    }

    protected function buildExpressionAttributeValues(array $keyConditions, array $filterConditions, array $fieldMapping): array
    {
        $conditions = array_merge($keyConditions, $filterConditions);
        $values = [];
        foreach ($conditions as $condition) {
            $fieldName = $condition['fieldName'];
            $fieldValue = $condition['fieldValue'];
            $operator = $condition['operator'];
            $type = $fieldMapping[$fieldName];
            if ($operator == 'BETWEEN') {
                foreach ($fieldValue as $key => $value) {
                    $values[":{$fieldName}.{$key}"] = $this->convertValueByType($value, $type);
                }
            } elseif ($operator == 'IN') {
                foreach ($fieldValue as $key => $item) {
                    $attributeValuePlaceholder = ":{$fieldName}_{$key}";
                    $values[$attributeValuePlaceholder] = $this->convertValueByType($item, $type);
                }
            } else {
                $values[":{$fieldName}"] = $this->convertValueByType($fieldValue, $type);
            }
        }

        return $values;
    }

    public function convertValueByType($value, $type)
    {
        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                return boolval($value);
            default:
                return strval($value);
        }
    }
}