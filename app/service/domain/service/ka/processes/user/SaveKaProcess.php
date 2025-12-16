<?php

namespace Imee\Service\Domain\Service\Ka\Processes\User;

use Imee\Models\Xs\XsRicher;
use Imee\Helper\Traits\GetListTrait;
use Imee\Service\Domain\Context\Ka\User\SaveKaContext;
use Imee\Models\Xsst\Ka\BmsKaUserList;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserCountry;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Comp\Gid\Models\Xsst\XsstGidRelationshipGroup;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserExp;
use Imee\Exception\Ka\UserException;
use Imee\Service\Operate\User\UserPriceLevelService;

class SaveKaProcess
{
    use GetListTrait;
    /**
     * @var XsUserExp
     */
    private $expModel = XsUserExp::class;

    /**
     * @var XsRicher
     */
    private $richerModel = XsRicher::class;

    /**
     * @var BmsKaUserList
     */
    private $kaModel = BmsKaUserList::class;

    private $type;

    private $uid;

    private $appDate;

    private $createError;

    public function __construct(SaveKaContext $context)
    {
        $this->type = $context->type ?? '';
        $this->uid = $context->uid ?? '';
        $this->appDate = $context->appDate ?? strtotime(date('Y-m-d'));
    }

    //通过邀请码生成ka
    public function invite()
    {
        $conditions = [
            '_model'    => $this->richerModel,
        ];
        //type allj就是初始化生成全部ka
        if (empty($this->type)) {
            $conditions['dateline'] = ['>=', $this->appDate - 86400]; //获取从昨天到现在的新增
        }
        $time = time();
        foreach ($this->getList($conditions, 'uid', 'id asc', 100) as $list) {
            if (!$list->valid()) {
                break;
            }
            foreach ($list as $item) {
                $exist = $this->kaModel::findFirst([
                    'conditions'    => 'uid = :uid:',
                    'bind'          => ['uid' => $item->uid]
                ]);
                if (empty($exist)) {
                    $newKa = $this->formatData($item->uid, $this->kaModel::KA_SOURCE['invite']);
                    if (!empty($newKa)) {
                        $newKa['create_time'] = $time;
                        $newKa['source'] = $this->kaModel::KA_SOURCE['invite'];
                        $this->kaModel::add($newKa);
                    }
                } else {
                    $newKa = $this->formatData($item->uid, 1);
                    if (!empty($newKa)) {
                        $newKa['update_time'] = $time;
                        $this->kaModel::edit($item->uid, $newKa);
                    }else{
                        $exist->delete();
                    }
                }
            }
        }
        return;
    }

    //根据财富等级生成ka
    public function expLv()
    {
        //ka最低标准
        $minKaLv = 0;
        foreach ($this->kaModel::KA_TAG_AREA_MAP as $value) {
            $preKaStart = $value['pre-KA']['start'];
            if (empty($minKaLv)) {
                $minKaLv = $preKaStart;
            } elseif (!empty($minKaLv) && $preKaStart < $minKaLv) {
                $minKaLv = $preKaStart;
            }
        }
        $conditions = [
            '_model'    => $this->expModel,
            'lv'        => ['>=', $minKaLv],
            'app_id'    => APP_ID
        ];

        foreach ($this->getList($conditions, 'uid', 'id asc', 100) as $list) {
            if (!$list->valid()) {
                break;
            }
            foreach ($list as $item) {
                $existKa = $this->kaModel::findFirst([
                    'conditions'    => 'uid = :uid:',
                    'bind'          => ['uid' => $item->uid]
                ]);
                if (empty($existKa)) {
                    $newKa = $this->formatData($item->uid);
                    if (!empty($newKa)) {
                        $newKa['create_time'] = $this->appDate;
                        $newKa['source'] = $this->kaModel::KA_SOURCE['expLv'];
                        $this->kaModel::add($newKa);
                    }
                } else {
                    $newKa = $this->formatData($item->uid);
                    if (!empty($newKa)) {
                        $newKa['update_time'] = $this->appDate;
                        $this->kaModel::edit($item->uid, $newKa);
                    } else {
                        $existKa->delete();
                    }
                }
            }
        }
        return;
    }

    public function create()
    {
        $newKa = $this->formatData($this->uid);
        if (!empty($newKa)) {
            $newKa['create_time'] = $this->appDate;
            $newKa['source'] = $this->kaModel::KA_SOURCE['create'];
            $this->kaModel::add($newKa);
            return true;
        } elseif (!empty($this->createError)) {
            UserException::throwException($this->createError);
        } else {
            UserException::throwException(UserException::ILLEGAL_ERROR);
        }
    }

    //更新ka全量数据
    public function update()
    {
        $conditions = [
            '_model'    => $this->kaModel
        ];
        foreach ($this->getListByPk($conditions, 'uid', 'uid', 'uid asc', 100) as $list) {
            if (!$list->valid()) {
                break;
            }
            foreach ($list as $item) {
                $newKa = $this->formatData($item->uid);
                if (!empty($newKa)) {
                    $newKa['update_time'] = $this->appDate;
                    $this->kaModel::edit($item->uid, $newKa);
                } else {
                    $this->kaModel::deleteById($item->uid);
                }
            }
        }
    }

    private function formatData($uid, $type = '')
    {
        //用户大区
        $area = XsUserBigarea::getUserArea($uid);
        $kaTag = $this->kaModel::getKaTag($uid, $area, APP_ID);
        //通过邀请码生成的ka 如果没有财富等级则为preka
        if ($type == $this->kaModel::KA_SOURCE['invite'] && empty($kaTag)) {
            $kaTag = 'pre-KA';
        }
        if (empty($kaTag)) {
            $type == $this->kaModel::KA_SOURCE['create'] && $this->createError = ['11', 'ka等级不达标'];
            return '';
        }
        // 查询用户信息
        $userProfile = XsUserProfile::findFirstValue($uid, 'app_id,pay_room_money,title');
        if (empty($userProfile)) {
            $type == $this->kaModel::KA_SOURCE['create'] && $this->createError = UserException::USER_NOT_EXIST;
            return '';
        }
        // 处理国家
        $userCountry = XsUserCountry::getUserCountryBatch([$uid]);
        $userCountry = !empty($userCountry[$uid]['country']) ? $userCountry[$uid]['country'] : '';
        //公会
        $brokerId = XsBrokerUser::getBrokerUserBatch([$uid]);
        $brokerId = $brokerId[$uid] ? $brokerId[$uid]['bid'] : 0;
        //大小号
        $gid = XsstGidRelationshipGroup::getUserGroup($uid);
        $gid = $gid ? $gid[0]['gid'] : 0;
        //关联账号最后一次登录
        $userRelationDate = XsUserProfile::getGidLastOnline($gid);

        $priceLevel = UserPriceLevelService::getInstance()->getList([$uid]);
        return [
            'uid'                   => $uid,
            'app_id'                => $userProfile->app_id ?? APP_ID,
            'vip'                   => $priceLevel[$uid] ?? 0,
            'broker_id'             => $brokerId,
            'user_relation_time'    => ceil(($this->appDate - $userRelationDate) / 86400),
            'user_relation_date'    => $userRelationDate,
            'big_id'                => XsUserProfile::getBigUid($gid),
            'gid'                   => $gid,
            'area'                  => $area,
            'country'               => $userCountry,
            'ka_tag'                => $kaTag,
            'title'                 => $userProfile->title ?? 0,
        ];
    }
}
