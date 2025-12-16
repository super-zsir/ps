<?php

namespace Imee\Service\Operate;

use Imee\Comp\Common\Fixed\TreeServ;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCategory;
use Imee\Models\Xs\XsCategoryLevel;
use Imee\Models\Xs\XsCategoryTag;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class CategoryService
{
    public function treeList($params)
    {
        $conditions = $this->packConditions($params);
        $res = XsCategory::getListByWhere($conditions, 'cid,pid,name,type,deleted,dpath,ordering', 'pid asc, ordering asc, cid asc');
        if (empty($res)) {
            return [];
        }

        $tree = new TreeServ();
        $tree->load(array('data' => $res, 'node' => 'cid', 'pnode' => 'pid'));
        return $tree->genTree();
    }

    private function packConditions($params): array
    {
        $conditions = [];
        if (empty($params['language'])) {
            $conditions[] = ['language', '=', 'zh_cn'];
        } else {
            $conditions[] = ['language', '=', $params['language']];
        }
        if (!empty($params['pid'])) {
            $conditions[] = ['pid', '=', $params['pid']];
        }
        if (isset($params['deleted'])) {
            $conditions[] = ['deleted', '=', $params['deleted']];
        }
        return $conditions;
    }

    public function getListAndTotal($params): array
    {
        $conditions = $this->packConditions($params);

        $result = XsCategory::getListAndTotal(
            $conditions, '*', 'pid asc, ordering asc, cid asc', $params['page'], $params['limit']
        );
        if (empty($result['data'])) {
            return $result;
        }
        foreach ($result['data'] as &$val) {
            $val['example_url'] = Helper::getHeadUrl($val['example']);
            $val['icon_url'] = Helper::getHeadUrl($val['icon']);;
        }

        return $result;
    }

    public function options(): array
    {
        return [
            'type'            => StatusService::formatMap(XsCategory::$typeMap, StatusService::PARAMS_FORMAT),
            'use_skill_cover' => StatusService::formatMap(XsCategory::$useSkillCoverMap, StatusService::PARAMS_FORMAT),
            '_display'        => StatusService::formatMap(XsCategory::$displayMap, StatusService::PARAMS_FORMAT),
            'service_uid'     => StatusService::formatMap(XsCategory::$serviceUidMap, StatusService::PARAMS_FORMAT),
            'video_need'      => StatusService::formatMap(XsCategory::$videoNeedMap, StatusService::PARAMS_FORMAT),
            'audio_need'      => StatusService::formatMap(XsCategory::$audioNeedMap, StatusService::PARAMS_FORMAT),
            'deleted'         => StatusService::formatMap(XsCategory::$deletedMap, StatusService::PARAMS_FORMAT),
            'language'        => StatusService::formatMap(XsBigarea::getLanguageArr(), StatusService::PARAMS_FORMAT),
        ];
    }

    public function detail($cid): array
    {
        $rec = XsCategory::findOne($cid);
        if (!$rec) {
            throw new ApiException(ApiException::MSG_ERROR, 'cid is error');
        }
        $data = $rec;
        $data['price'] = intval($data['price'] / 100);
        $data['free'] = intval($data['free'] / 100);
        $data['pid'] = intval($data['pid']);
        $data['level'] = strval($data['level']);
        $data['dpath'] = intval($data['dpath']);
        $data['deleted'] = strval($data['deleted']);
        $data['audio_need'] = strval($data['audio_need']);
        $data['video_need'] = strval($data['video_need']);
        $data['game_id_need'] = strval($data['game_id_need']);
        $data['duration'] = intval($data['duration']);
        $data['_display'] = strval($data['_display']);
        $data['use_skill_cover'] = strval($data['use_skill_cover']);
        $data['service_uid'] = strval($data['service_uid']);
        $data['example_url'] = Helper::getHeadUrl($data['example']);
        $data['icon_url'] = Helper::getHeadUrl($data['icon']);;
        return $data;
    }

    public function status($cid, $deleted): bool
    {
        if (!in_array($deleted, array(0, 1))) {
            throw new ApiException(ApiException::MSG_ERROR, 'deleted参数错误');
        }
        $origin = XsCategory::findOne($cid);
        if (!$origin) {
            throw new ApiException(ApiException::MSG_ERROR, 'cid不存在');
        }
        if ($deleted != $origin['deleted']) {
            XsCategory::edit($cid, ['deleted' => $deleted]);
            if ($deleted == 1) {
                $this->publishDelete($cid);
            }
        }
        return true;
    }

    private function publishDelete($cid)
    {
        //删除发消息
        NsqClient::publish('xs.admin', array(
            'cmd'  => 'delete.category',
            'data' => array(
                'cid' => $cid,
            )
        ));
    }

    public function create($params): array
    {
        if (empty($params['language'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'language 必须');
        }

        [$result, $data] = $this->_getCategoryParams($params);
        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }
        $data['deleted'] = 1;
        $data['language'] = trim($params['language']);
        [$result, $cid] = XsCategory::add($data);
        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $cid);
        }

        //$this->publishDelete($cid);
        return [$result, $cid];
    }

    public function modify($cid, $params): array
    {
        $origin = XsCategory::findOne($cid);
        if (!$origin) {
            throw new ApiException(ApiException::MSG_ERROR, 'cid不存在');
        }

        [$result, $data] = $this->_getCategoryParams($params);
        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $data);
        }

        return XsCategory::edit($cid, $data);
    }

    /**
     * if(dpath == 0){
     * disAllow = ['duration', 'use_skill_cover', 'cond', 'game_id_need', 'video_need', 'video_id', 'video_description', 'icon', 'type', 'pid', 'audio_need', 'types', 'free', 'price', 'uint', 'manageTag', 'managePrice', 'manageLevel', 'example', 'level', 'audio_description', 'cover_description', 'service_uid', 'auth', 'description'];
     * if(dpath == 1){
     * disAllow = ['duration', 'use_skill_cover', 'type', 'price', 'free', 'uint', 'manageTag', 'types', 'managePrice'];
     * if(dpath == 2){
     * disAllow = ['cond', 'game_id_need', 'video_need', 'video_id', 'video_description', 'auth', 'cover_description', 'audio_description', 'service_uid', 'level', 'manageLevel', 'example', 'audio_need'];
     * @param $params
     * @return array
     */
    private function _getCategoryParams($params): array
    {
        $data = [];
        $data['pid'] = intval($params['pid'] ?? 0);
        $data['ordering'] = max(0, intval($params['ordering'] ?? 0));
        $data['price'] = intval($params['price'] ?? 0);
        $data['level'] = intval($params['level'] ?? 0);
        $data['audio_need'] = intval($params['audio_need'] ?? 0);
        $data['service_uid'] = intval($params['service_uid'] ?? 0);
        $data['free'] = intval($params['free'] ?? 0);
        $data['name'] = trim($params['name']);
        $data['intro'] = trim($params['intro'] ?? '');
        $data['icon'] = trim($params['icon'] ?? '');
        $data['type'] = trim($params['type'] ?? '');
        $data['uint'] = trim($params['uint'] ?? '');
        $data['auth'] = trim($params['auth'] ?? '');
        $data['description'] = trim($params['description'] ?? '');
        $data['cover_description'] = trim($params['cover_description'] ?? '');
        $data['audio_description'] = trim($params['audio_description'] ?? '');
        $data['example'] = trim($params['example'] ?? '');
        $data['types'] = trim($params['types'] ?? '');
        $data['game_id_need'] = intval($params['game_id_need'] ?? 0);
        $data['video_need'] = intval($params['video_need'] ?? 0);
        $data['video_id'] = intval($params['video_id'] ?? 0);
        $data['video_description'] = trim($params['video_description'] ?? '');
        $data['cond'] = trim($params['cond'] ?? '');
        $data['duration'] = intval($params['duration'] ?? 0);
        $data['rate'] = intval($params['rate'] ?? 0);
        $data['use_skill_cover'] = intval($params['use_skill_cover'] ?? 0) > 0 ? 1 : 0;
        $data['_name'] = trim($params['_name'] ?? '');
        $data['_display'] = intval($params['_display'] ?? 0);
        if ($data['_display'] > 0) {
            $data['_display'] = 1;
        } else {
            $data['_display'] = 0;
        }

        //基本，都必须要有
        if (empty($data['name'])) {
            return [false, 'name为空'];
        }

        if ($data['pid'] == 0) {
            $dpath = 0;
        } else {
            $category = XsCategory::findOne($data['pid']);
            if (!$category) {
                return [false, 'pid不存在'];
            }
            $dpath = intval($category['dpath']) + 1;
        }

        $data['dpath'] = $dpath;
        if ($data['dpath'] < 0 || $data['dpath'] > 2) {
            return [false, 'dpath 0 - 2'];
        }

        if ($dpath == 0) {
            $data['type'] = 'all';
            $data['uint'] = '';
            $data['icon'] = '';
            $data['price'] = 0;
            $data['level'] = 0;
            $data['dpath'] = 0;
            $data['free'] = 0;
            $data['auth'] = 'none';
            $data['cover_description'] = '';
            $data['audio_description'] = '';
            $data['example'] = '';
            $data['types'] = '';
            $data['cond'] = '';
            $data['video_description'] = '';
            $data['game_id_need'] = 0;
            $data['video_need'] = 0;
            $data['video_id'] = 0;
            $data['rate'] = 0;
        } elseif ($dpath == 1) {
            //用户申请大神用的
            if (empty($data['icon'])) {
                return [false, '请上传图标'];
            }
            if (!empty($data['auth']) && !in_array($data['auth'], array('none', 'normal', 'person'))) {
                return [false, '认证: none、normal、person'];
            }
            if (!empty($data['level']) && !in_array($data['level'], array(0, 1))) {
                return [false, '等级: 0、1'];
            }
            $data['type'] = 'all';
            $data['uint'] = '';
            $data['price'] = 0;
            $data['free'] = 0;
            $data['types'] = '';
            $data['rate'] = 0;
        } else {
            if (empty($data['icon'])) {
                return [false, '请上传图标'];
            }
            //用户最终使用的
            if (empty($data['uint'])) {
                return [false, '请填写单位'];
            }

            if (!in_array($data['type'], array('online', 'offline', 'text', 'video'))) {
                return [false, '类型不正确'];
            }

            if ($data['rate'] < 0 || $data['rate'] > 100) {
                return [false, '费率0-100'];
            }

            if ($data['price'] <= 0) {
                return [false, '价格需要 > 0'];
            }
            $data['price'] = $data['price'] * 100;
            $data['free'] = $data['free'] * 100;
            if ($data['type'] == 'offline' && empty($data['types'])) {
                return [false, '类型为线下时，高德地图类别不能为空'];
            }
            if ($data['free'] >= $data['price']) {
                return [false, '优惠不能>=价格'];
            }

            $data['level'] = 0;
            $data['auth'] = 'none';
            $data['cond'] = '';
            $data['video_description'] = '';
            $data['game_id_need'] = 0;
            $data['video_need'] = 0;
            $data['video_id'] = 0;
        }

        $data['service_uid'] = 0;
        return [true, $data];
    }

    public function getCategoryTagList($params): array
    {
        $conditions = [];
        if (!empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }
        return XsCategoryTag::getListByWhere($conditions, '*', 'tid desc');
    }

    public function addCategoryTag($params): array
    {
        if (empty($params['cid'])) {
            return [false, 'cid不能为空，请确认'];
        }
        $category = XsCategory::findFirst($params['cid']);
        if (!$category) {
            return [false, '当前品类不存在，请确认'];
        }

        if (empty($params['title'])) {
            return [false, '标签名称不能为空，请确认'];
        }
        if (!isset($params['sex']) || !in_array($params['sex'], array(0, 1, 2))) {
            return [false, 'sex提交请求参数错误'];
        }

        $rec = XsCategoryTag::findFirst(array(
            'conditions' => "cid = {$params['cid']} and title = :title: ",
            'bind'       => array(
                'title' => $params['title']
            ),
        ));

        if ($rec) {
            return [false, '该标签已经存在，请确认'];
        }

        $insert = [];
        $insert['cid'] = $params['cid'];
        $insert['title'] = $params['title'];
        $insert['deleted'] = $params['deleted'] > 0 ? 1 : 0;
        $insert['sex'] = $params['sex'];
        return XsCategoryTag::add($insert);
    }

    public function modifyCategoryTag($params): array
    {
        if (empty($params['tid'])) {
            return [false, 'tid不能为空，请确认'];
        }
        if (empty($params['title'])) {
            return [false, '标签名称不能为空，请确认'];
        }
        if (!isset($params['sex']) || !in_array($params['sex'], array(0, 1, 2))) {
            return [false, 'sex提交请求参数错误'];
        }

        $rec = XsCategoryTag::findOne($params['tid']);
        if (!$rec) {
            return [false, '当前数据不存在，请确认'];
        }

        $find = XsCategoryTag::findFirst(array(
            'conditions' => "cid = {$rec['cid']} and title = :title: ",
            'bind'       => array(
                'title' => $params['title']
            ),
        ));
        if ($find && $find->tid != $params['tid']) {
            return [false, '该标签已经存在，请确认'];
        }

        $insert = [];
        $insert['title'] = $params['title'];
        $insert['deleted'] = $params['deleted'] > 0 ? 1 : 0;
        $insert['sex'] = $params['sex'];
        return XsCategoryTag::edit($params['tid'], $insert);
    }

    public function getCategoryLevelList($params): array
    {
        $conditions = [];
        if (!empty($params['cid'])) {
            $conditions[] = ['cid', '=', $params['cid']];
        }
        return XsCategoryLevel::getListByWhere($conditions, '*', 'id desc');
    }

    public function addCategoryLevel($params): array
    {
        if (empty($params['cid'])) {
            return [false, 'cid不能为空，请确认'];
        }
        $category = XsCategory::findFirst($params['cid']);
        if (!$category) {
            return [false, '当前品类不存在，请确认'];
        }
        if (empty($params['title'])) {
            return [false, '等级名称不能为空，请确认'];
        }
        $rec = XsCategoryLevel::findFirst(array(
            'conditions' => "cid = {$params['cid']} and title = :title: ",
            'bind'       => array(
                'title' => $params['title']
            ),
        ));
        if ($rec) {
            return [false, '该等级已经存在，请确认'];
        }
        $insert = [];
        $insert['cid'] = $params['cid'];
        $insert['title'] = $params['title'];
        $insert['deleted'] = $params['deleted'] > 0 ? 1 : 0;
        $insert['ordering'] = $params['ordering'];
        return XsCategoryLevel::add($insert);
    }

    public function modifyCategoryLevel($params): array
    {
        if (empty($params['id'])) {
            return [false, 'id不能为空，请确认'];
        }
        if (empty($params['title'])) {
            return [false, '等级名称不能为空，请确认'];
        }
        $rec = XsCategoryLevel::findOne($params['id']);
        if (!$rec) {
            return [false, '当前数据不存在，请确认'];
        }
        $find = XsCategoryLevel::findFirst(array(
            'conditions' => "cid = {$rec['cid']} and title = :title: ",
            'bind'       => array(
                'title' => $params['title']
            ),
        ));
        if ($find && $find->id != $params['id']) {
            return [false, '该等级已经存在，请确认'];
        }
        $insert = [];
        $insert['title'] = $params['title'];
        $insert['deleted'] = $params['deleted'] > 0 ? 1 : 0;
        $insert['ordering'] = $params['ordering'];
        return XsCategoryLevel::edit($params['id'], $insert);
    }
}