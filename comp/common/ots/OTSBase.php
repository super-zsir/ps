<?php

namespace Imee\Comp\Common\Ots;

use Aliyun\OTS\OTSClient;
use Aliyun\OTS\Consts\ReturnTypeConst;
use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\Consts\SortOrderConst;
use Imee\Comp\Common\Log\LoggerProxy;
use Config\ConfigAliyunOts;

class OTSBase
{
    const DESC = DirectionConst::CONST_BACKWARD;
    const ASC = DirectionConst::CONST_FORWARD;

    const SEARCH_SORT_DESC = SortOrderConst::SORT_ORDER_DESC;
    const SEARCH_SORT_ASC = SortOrderConst::SORT_ORDER_ASC;

    const INF_MAX = PrimaryKeyTypeConst::CONST_INF_MAX;
    const INF_MIN = PrimaryKeyTypeConst::CONST_INF_MIN;

    const COND_EXPECT_EXIST = RowExistenceExpectationConst::CONST_EXPECT_EXIST;
    const COND_EXPECT_NOT_EXIST = RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST;
    const COND_IGNORE = RowExistenceExpectationConst::CONST_IGNORE;

    private $_tableName;
    private $_instanceName;
    private $_endPoint;

    public $_client;

    const SCHEMA = '';//配置instanceName
    const TABLE_NAME = '';//可选，自定义表名

    public function __construct($instanceName = null, $endPoint = null)
    {
        $this->_instanceName = $this->getInstanceName($instanceName);
        $this->_endPoint = $this->getEndPoint($endPoint);
        $this->_tableName = $this->getTableName();
        $this->_client = $this->getClient();
    }

    private function getInstanceName($instanceName)
    {
        if ($instanceName) {
            return $instanceName;
        }
        return static::SCHEMA;
    }

    private function getEndPoint($endPoint)
    {
        if ($endPoint) {
            return $endPoint;
        }

        $endPoint = ENV === 'dev' ? ConfigAliyunOts::EndPointDev : ConfigAliyunOts::EndPoint;
        return str_replace('{instanceName}', $this->_instanceName, $endPoint);
    }

    /**
     * @param array $pk $pk =[['uid', intval($tpuid)], ['topic_id', intval($tpid)]];
     * @param $condition
     * @return bool
     */
    public function delete(array $pk, $condition = self::COND_EXPECT_EXIST)
    {
        $request = [
            'table_name'     => $this->_tableName,
            'condition'      => $condition,
            'primary_key'    => $pk,
            'return_content' => [
                'return_type' => ReturnTypeConst::CONST_PK
            ]
        ];
        try {
            $this->_client->deleteRow($request);
            return true;
        } catch (\Exception $e) {
            //使用这种情况，可以减少一个write单元费用
            if (
                $condition == self::COND_EXPECT_EXIST
                && strpos($e->getMessage(), 'Condition check failed') !== false
            ) {
                return true;
            }
            return false;
        }
    }

    /**
     * @param array $pk $pk =[['uid', intval($tpuid)], ['topic_id', intval($tpid)]];
     * @param array $update [['uid', intval($tpuid)], ['topic_id', intval($tpid)]];
     * @param $condition
     * @return bool
     */
    public function update(array $pk, array $update, $condition = self::COND_EXPECT_EXIST)
    {
        $request = [
            'table_name'                  => $this->_tableName,
            'condition'                   => $condition,
            'primary_key'                 => $pk,
            'update_of_attribute_columns' => ['PUT' => $update],
            'return_content'              => ['return_type' => ReturnTypeConst::CONST_PK]
        ];
        try {
            $this->_client->updateRow($request);
            return true;
        } catch (\Exception $e) {
            if (($condition == self::COND_EXPECT_EXIST || $condition == self::COND_EXPECT_NOT_EXIST)
                && strpos($e->getMessage(), 'Condition check failed') !== false
            ) {
                return true;
            }
            return false;
        }
    }

    public function getRange(array $pkStart, array $pkEnd, $dir = self::DESC, array $filter = null, $limit = 100)
    {
        $request = [
            'table_name'                  => $this->_tableName,
            'max_versions'                => 1,
            'direction'                   => $dir,
            'inclusive_start_primary_key' => $pkStart,
            'exclusive_end_primary_key'   => $pkEnd,
            'limit'                       => $limit,
        ];
        if (!empty($filter)) {
            $request['column_filter'] = $filter;
        }
        try {
            $response = $this->_client->getRange($request);
            return $this->formatRangeRows($response);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    public function query(array $request)
    {
        try {
            if (!isset($request['table_name'])) {
                $request['table_name'] = $this->_tableName;
            }
            $response = $this->_client->search($request);
            return $this->formatSearchRows($response);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * @param array $pk $pk =[['uid', intval($tpuid)], ['topic_id', intval($tpid)]];
     * @return array|false|null
     */
    public function getRow(array $pk)
    {
        $request = [
            'table_name'     => $this->_tableName,
            'primary_key'    => $pk,
            'max_versions'   => 1,
            'return_content' => ['return_type' => ReturnTypeConst::CONST_PK]
        ];
        try {
            $response = $this->_client->getRow($request);
            return $this->formatRow($response);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
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
        $request = [
            'tables' => [
                [
                    'table_name'     => $this->_tableName,
                    'primary_keys'   => $pks,
                    'max_versions'   => 1,
                    'return_content' => ['return_type' => ReturnTypeConst::CONST_PK]
                ]
            ]
        ];
        try {
            $response = $this->_client->batchGetRow($request);
            $data = [];
            foreach ($response['tables'] as $all) {
                if ($all['table_name'] == $this->_tableName) {
                    foreach ($all['rows'] as $val) {
                        if ($val['is_ok'] > 0) {
                            $item = array();
                            foreach ($val['primary_key'] as $v) {
                                $item[$v[0]] = $v[1];
                            }
                            foreach ($val['attribute_columns'] as $v) {
                                $item[$v[0]] = $v[1];
                            }
                            $data[] = $item;
                        }
                    }
                }
            }
            return $data;
        } catch (\Exception $e) {
            $this->logError($e);
            return [];
        }
    }

    protected function formatRangeRows($response)
    {
        $data = [];
        foreach ($response['rows'] as $val) {
            $item = [];
            foreach ($val['primary_key'] as $v) {
                $item[$v[0]] = $v[1];
            }
            foreach ($val['attribute_columns'] as $v) {
                $item[$v[0]] = $v[1];
            }
            $data[] = $item;
        }
        return [
            'data'           => $data,
            'pk_next'        => $response['next_start_primary_key'],
            'pk_next_string' => $this->nextToString($response['next_start_primary_key']),
        ];
    }

    protected function formatSearchRows($response)
    {
        $data = [];
        foreach ($response['rows'] as $val) {
            $item = [];
            foreach ($val['primary_key'] as $v) {
                $item[$v[0]] = $v[1];
            }
            foreach ($val['attribute_columns'] as $v) {
                $item[$v[0]] = $v[1];
            }
            $data[] = $item;
        }
        return [
            'data'           => $data,
            'total'          => $response['total_hits'],
            'pk_next_string' => base64_encode($response['next_token']),
            'next_token'     => $response['next_token'],
        ];
    }

    protected function formatRow($response)
    {
        if (empty($response['primary_key'])) {
            return null;
        }
        $data = [];
        foreach ($response['primary_key'] as $val) {
            $data[$val[0]] = $val[0];
        }
        foreach ($response['attribute_columns'] as $val) {
            $data[$val[0]] = $val[0];
        }
        return $data;
    }

    protected function nextToString($next)
    {
        if (empty($next)) return null;
        $values = [];
        foreach ($next as $val) {
            $values[] = $val[0];
            $values[] = $val[1];
        }
        return implode(',', $values);
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
        $key = $this->_instanceName;
        $key = str_replace(' ', '', ucwords(str_replace('-', ' ', $key)));
        $id = $key . 'AccessKeyID';
        $secret = $key . 'AccessKeySecret';

        $class = new \ReflectionClass('Config\ConfigAliyunOts');
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

        return new OTSClient([
            'EndPoint'        => $this->_endPoint,
            'AccessKeyID'     => $ak,
            'AccessKeySecret' => $sk,
            'InstanceName'    => $this->_instanceName,
            'SocketTimeout'   => 5,
        ]);
    }

    protected function logError(\Exception $e)
    {
        LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
    }
}