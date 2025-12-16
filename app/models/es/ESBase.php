<?php

namespace Imee\Models\Es;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Elasticquent\ElasticquentTrait;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

abstract class ESBase extends Model
{
    use ElasticquentTrait;

    protected $search = [];

    /**
     * @desc  转化 where 条件为 ElasticSearch 搜索 DSL
     *
     * @param $where
     * @param string $tableName
     * @param array $orderBy
     * @param bool $useScroll
     * @return $this
     */
    public function createWhere($where, $tableName = 'es_table', $orderBy = [], $useScroll = false)
    {
        $this->search = $this->createSearch($where, $tableName, $orderBy, $useScroll);

        return $this;
    }

    /**
     * @desc  批量搜索
     *
     * @param $count
     * @param callable $callback
     */
    public function chunk($count, callable $callback)
    {
        try {
            $this->search['body']['sort'] = '_doc';
            $this->search['body']['size'] = $count;
            $result = $this->scrollSearch($this->search);
            $callback($result->toArray());

            do {
                $result = $this->scrollSearchGetData($result->scroll_id);

                $callback($result->toArray());
            } while (!empty($result->getHits()['hits']));
        } catch (\Exception $e) {
            NsqClient::publish(NsqConstant::TOPIC_COMPANY_WECHAT, array(
                'cmd' => 'sendText',
                'data' => array(
                    'chatid' => 'Ourea',
                    'title' => 'es异常',
                    'content' => $e->getMessage() . " trace: " . $e->getTraceAsString(),
                ),
                'dateline' => time(),
            ));
        }
    }

    public function paginate($count = 20)
    {
        $result = $this->paginateSearch($count, $this->search);
        $total = $result->getHits()['total'];
        return new LengthAwarePaginator($result->toArray(), $total, $count);
    }

    /**
     * @desc 分页搜索
     *
     * @param int $perPage
     * @param array $search
     * @param array $columns
     * @param string $pageName
     * @param null $page
     * @return \Elasticquent\ElasticquentResultCollection
     */
    public function paginateSearch($perPage = 20, $search = [], $columns = ['*'], $pageName = 'page', $page = 1)
    {
        //$tmp = json_encode($search);
        $search['size'] = $perPage ;
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        //$page = 1;
        $search['from'] = ($page - 1) * $perPage;
        $search['index'] = $this->getIndexName();
        $search['type'] = $this->getTypeName();

        $result = $this->complexSearch($search);

        return $result;
    }

    /**
     * @desc 搜索引擎导出
     *
     * @param array $search
     * @param string $scrollTime
     * @return \Elasticquent\ElasticquentResultCollection
     */
    public function scrollSearch($search = [], $scrollTime = '10m')
    {
        // $tmp = json_encode($search);
        $search['index'] = $this->getIndexName();
        $search['type'] = $this->getTypeName();
        // $search['search_type'] = 'scan';
        $search['scroll'] = $scrollTime;

        $result = $this->complexSearch($search);

        return $result;
    }

    /**
     * 当搜索结果为空时，返回空分页
     *
     * @param int $perPage
     * @param string $pageName
     * @param null $page
     * @return LengthAwarePaginator
     */
    public function getEmptyPage($perPage = 20, $pageName = 'page', $page = null)
    {
        return new LengthAwarePaginator([], 0, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * @desc  转化 where 条件为 ElasticSearch 搜索 DSL
     *
     * @param $where
     * @param string $tableName
     * @param array $orderBy
     * @param bool $useScroll
     * @param int $scroolSize
     * @return array
     */
    public function createSearch($where, $tableName = 'es_table', $orderBy = [], $useScroll = false, $scroolSize = 500)
    {
        $search['body']['query'] = $this->getESSearchDSL($where, $tableName);

        if ($useScroll) {
            $search['body']['sort'] = '_doc';
            $search['body']['size'] = $scroolSize;
        } else {
            if (empty($orderBy)) {
                $search['body']['sort'] = [$this->getKeyName() => ['order' => 'asc']];
            } else {
                foreach ($orderBy as $k => $v) {
                    $search['body']['sort'][] = [$k => ['order' => $v]];
                }
            }
        }

        return $search;
    }

    private function getESSearchDSL($where, $tableName = 'es_table')
    {
        $orWheres = ! empty($where['or']) ? $where['or'] : [];
        unset($where['or']);

        $table_group = $this->getTableGroup($where, $tableName);
        if (empty($table_group[$tableName])) {
            $table_group[$tableName] = [];
        }

        $search = [];
        $search['bool']['must'] = $this->buildSearch($table_group[$tableName]);

        unset($table_group[$tableName]);

        foreach ($table_group as $table => $where) {
            $nestd_search['bool']['must'] = $this->buildSearch($where);

            $search['bool']['must'][] = [
                'nested' => [
                    'path' => $table,
                    'query' => $nestd_search,
                ],
            ];
        }

        foreach ($orWheres as $orWhere) {
            $or_search['bool']['should'] = $this->getESOrSearchDSL($orWhere, $tableName);
            $or_search['bool']['minimum_should_match'] = 1;
            $search['bool']['must'][] = $or_search;
        }

        return $search;
    }

    private function getESOrSearchDSL($where, $tableName = 'es_table')
    {
        $orWheres = ! empty($where['or']) ? $where['or'] : [];
        unset($where['or']);

        $index = str_replace('.', '_', $this->getIndexName());
        foreach ($where as $k => $v) {
            if (in_array($k, ['nest'])) {
                continue;
            }
            unset($where[$k]);
            $where[$index . '.' . $k] = $v;
        }

        $table_group = $this->getTableGroup($where, $tableName);
        if (empty($table_group[$tableName])) {
            $table_group[$tableName] = [];
        }

        $search = [];
        $nestWheres = ! empty($where['nest']) ? $where['nest'] : [];
        unset($where['nest']);

        $search = $this->buildSearch($table_group[$tableName]);

        $nest_search = $this->getESSearchDSL($nestWheres, $tableName);

        if (! empty($nestWheres)) {
            $search[] = $nest_search;
        }

        unset($table_group[$tableName]);

        foreach ($table_group as $table => $where) {
            $nestd_search = $this->buildSearch($where);
            ;

            $search[]['bool']['must'][] = [
                'nested' => [
                    'path' => $table,
                    'query' => $nestd_search,
                ],
            ];
        }

        foreach ($orWheres as $orWhere) {
            $or_search['bool']['should'] = $this->getESOrSearchDSL($orWhere, $tableName);
            $or_search['bool']['minimum_should_match'] = 1;
            $search[]['bool']['must'][] = $or_search;
        }

        return $search;
    }

    private function getTableGroup($where, $tableName)
    {
        $table_group = [];

        if (isset($where['in'])) {
            foreach ($where['in'] as $k => $v) {
                if (strpos($k, '.') != false) {
                    $ext = explode('.', $k);

                    if ($ext[0] == $tableName) {
                        $table_group[$ext[0]]['in'][$ext[1]] = $v;
                    } else {
                        $table_group[$ext[0]]['in'][$k] = $v;
                    }
                }
            }
            unset($where['in']);
        }
        if (isset($where['not_in'])) {
            foreach ($where['not_in'] as $k => $v) {
                if (strpos($k, '.') != false) {
                    $ext = explode('.', $k);

                    if ($ext[0] == $tableName) {
                        $table_group[$ext[0]]['not_in'][$ext[1]] = $v;
                    } else {
                        $table_group[$ext[0]]['not_in'][$k] = $v;
                    }
                }
            }
            unset($where['not_in']);
        }
        if (isset($where['missing'])) {
            foreach ($where['missing'] as $k => $v) {
                if (strpos($k, '.') != false) {
                    $ext = explode('.', $k);

                    if ($ext[0] == $tableName) {
                        $table_group[$ext[0]]['missing'][$ext[1]] = $v;
                    } else {
                        $table_group[$ext[0]]['missing'][$k] = $v;
                    }
                }
            }
            unset($where['missing']);
        }
        if (isset($where['exist'])) {
            foreach ($where['exist'] as $k => $v) {
                if (strpos($k, '.') != false) {
                    $ext = explode('.', $k);

                    if ($ext[0] == $tableName) {
                        $table_group[$ext[0]]['exist'][$ext[1]] = $v;
                    } else {
                        $table_group[$ext[0]]['exist'][$k] = $v;
                    }
                }
            }
            unset($where['exist']);
        }

        if ($where) {
            foreach ($where as $k => $v) {
                if (strpos($k, '.') != false) {
                    $ext = explode('.', $k);

                    if ($ext[0] == $tableName) {
                        $table_group[$ext[0]][$ext[1]] = $v;
                    } else {
                        $table_group[$ext[0]][$k] = $v;
                    }
                }
            }
        }

        return $table_group;
    }

    /**
     * @desc  转化 where 条件
     *
     * @param array $where
     * @return array
     */
    private function buildSearch($where =[])
    {
        $search = [];

        if (isset($where['in'])) {
            foreach ($where['in'] as $k => $v) {
                $search[]['bool']['must'] = ['terms' => [$k => $v]];
            }
            unset($where['in']);
        }
        if (isset($where['exist'])) {
            foreach ($where['exist'] as $k => $v) {
                $search[]['bool']['must'] = ['exists' => ['field' => $k]];
            }
            unset($where['exist']);
        }
        if (isset($where['missing'])) {
            foreach ($where['missing'] as $k => $v) {
                $search[]['bool']['must'] = ['missing' => ['field' => $k]];
            }
            unset($where['missing']);
        }
        if (isset($where['not_in'])) {
            foreach ($where['not_in'] as $k => $v) {
                $search[]['bool']['must_not'] = ['terms' => [$k => $v]];
            }
            unset($where['not_in']);
        }

        if ($where) {
            foreach ($where as $k => $v) {
                if (substr($k, -2) == ' <') {
                    $k = trim(str_replace(' <', '', $k));
                    $search[]['bool']['must'] = ['range' => [$k => ['lt' => $v]]];
                } elseif (substr($k, -3) == ' <=') {
                    $k = trim(str_replace(' <=', '', $k));
                    $search[]['bool']['must'] = ['range' => [$k => ['lte' => $v]]];
                } elseif (substr($k, -2) == ' >') {
                    $k = trim(str_replace(' >', '', $k));
                    $search[]['bool']['must'] = ['range' => [$k => ['gt' => $v]]];
                } elseif (substr($k, -3) == ' >=') {
                    $k = trim(str_replace(' >=', '', $k));
                    $search[]['bool']['must'] = ['range' => [$k => ['gte' => $v]]];
                } elseif (substr($k, -5) == ' like') {
                    $k = trim(str_replace(' like', '', $k));
                    $search[]['bool']['must'] = ['match_phrase' => [$k => $v]];
                } elseif (substr($k, -3) == ' !=') {
                    $k = trim(str_replace(' !=', '', $k));
                    $search[]['bool']['must_not'] = ['term' => [$k => $v]];
                } elseif (substr($k, -3) == ' <>') {
                    $k = trim(str_replace(' <>', '', $k));
                    $search[]['bool']['must_not'] = ['term' => [$k => $v]];
                } else {
                    $search[]['bool']['must'] = ['term' => [$k => $v]];
                }
            }
        }

        return $search;
    }

    /**
     * @desc 导出查询数据
     *
     * @param string $scroll_id
     * @param string $scrollTime
     * @return \Elasticquent\ElasticquentResultCollection
     */
    public function scrollSearchGetData($scroll_id = '', $scrollTime = '1m')
    {
        $search['scroll_id'] = $scroll_id;
        $search['scroll'] = $scrollTime;

        $result = $this->complexScrollSearch($search);

        return $result;
    }

    public function eloquentToElastic($eloquentModel, $esModel)
    {
        foreach ($eloquentModel as $key => $attr) {
            $esModel->$key = $attr;
        }

        return $esModel;
    }

    public function createSearchByWhere($where)
    {
        $orWheres = isset($where['or']) && !empty($where['or']) ? $where['or'] : [];
        unset($where['or']);
        $index = str_replace('.', '_', $this->getIndexName());

        foreach ($where as $k => $v) {
            unset($where[$k]);
            $where[$index . '.' . $k] = $v;
        }

        return $this->getESSearchDSL(array_merge($where, ['or' => $orWheres]), $index);
    }
}
