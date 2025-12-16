<?php

namespace Imee\Comp\Common\Fixed;

class TreeServ
{
    protected $data;
    protected $pk;
    protected $parentKey;
    protected $tree;
    protected $root = self::PARENT_ID;

    const CHILDREN = 'children';
    const PARENT_ID = 0;

    public function __construct()
    {
    }

    public function getPk()
    {
        return $this->pk;
    }

    public function load(array $config)
    {
        if (!isset($config['node']) || !isset($config['pnode'])) {
            throw new \Exception("you should give node and pnode");
        }

        $this->pk = $config['node'];
        $this->parentKey = $config['pnode'];

        if (isset($config['data'])) {
            $this->data = $config['data'];
        }

        if (isset($config['root'])) {
            $this->root = $config['root'];
        }
    }

    public function genTree()
    {
        if ($this->tree === null) {
            $this->tree = $this->deepTree();
        }

        if ($this->tree) {
            $this->formatTree($this->tree);
        }
        return $this->tree;
    }

    protected function deepTree($root = 0)
    {
        if (!$this->data) {
            return null;
        }

        $originalList = $this->data;
        $tree = array();
        $refer = array();

        foreach ($originalList as $k => $v) {
            if (!isset($v[$this->pk]) || !isset($v[$this->parentKey]) || isset($v[self::CHILDREN])) {
                unset($originalList[$k]);
                continue;
            }
            $refer[$v[$this->pk]] = &$originalList[$k];
        }

        foreach ($originalList as $k => $v) {
            if ($this->isRoot($v[$this->parentKey])) {
                $tree[] = &$originalList[$k];
            } else {
                if (isset($refer[$v[$this->parentKey]])) {
                    $parent = &$refer[$v[$this->parentKey]];
                    $parent[self::CHILDREN][] = &$originalList[$k];
                }
            }
        }
        return $tree;
    }

    protected function formatTree(&$v)
    {
        foreach ($v as &$val) {
            if (isset($val['leaf']) && !$val['leaf']) {
                continue;
            }
            if (empty($val[self::CHILDREN])) {
                $val['leaf'] = true;
            } else {
                $this->formatTree($val[self::CHILDREN]);
            }
        }
    }

    protected function isRoot($v)
    {
        return $v == $this->root;
    }

    //根据字段权重排序
    public function orderBy($treeTmp=[],$column='weight')
    {
        if(empty($treeTmp)){
            $treeTmp = $this->tree;
        }
        array_multisort(array_column($treeTmp, $column), SORT_ASC, $treeTmp);
        $treeTmp = array_values($treeTmp);
        foreach($treeTmp as &$item){
            if(!empty($item['children'])){
                $item['children'] = $this->orderBy($item['children']);
            }
        }
        return $treeTmp;
    }
}