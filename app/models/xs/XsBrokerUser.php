<?php
namespace Imee\Models\Xs;

class XsBrokerUser extends BaseModel
{
	public static function getValue($id, $useMaster = false)
	{
		$conds = [
			'id = :id:',
			'bind' => ['id' => $id]
		];
		if ($useMaster) {
			return self::useMaster()->findFirst($conds);
		}
		return self::findFirst($conds);
	}

	public static function getValueByUid($uid, $useMaster = false)
	{
		$conds = [
			"uid=:uid:",
			"bind" => array("uid" => $uid)
		];
		if ($useMaster) {
			return self::useMaster()->findFirst($conds);
		}
		return self::findFirst($conds);
	}

	public static function has($bid, $uid, $useMaster = false)
	{
		$conds = [
			"bid=:bid: and uid=:uid:",
			"bind" => array("bid" => $bid, "uid" => $uid)
		];
		if ($useMaster) {
			return self::useMaster()->findFirst($conds);
		}
		return self::findFirst($conds);
	}

	public static function insertRows($bid, $uid)
	{
		$has = self::getValueByUid($uid);
		if ($has) return true;

		$rec = self::useMaster();
		$rec->bid = $bid;
		$rec->uid = $uid;
		$rec->deleted = 0;
		$rec->dateline = time();
		$rec->save();
		return true;
	}

    /**
     * 根据bid批量获取公会信息
     * @param array $uidArr uid
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getBrokerUserBatch($uidArr = [], $fieldArr = ['uid', 'bid'])
    {
        if (empty($uidArr)) {
            return [];
        }
        if (!in_array('uid', $fieldArr)) {
            $fieldArr[] = 'uid';
        }

        $data = self::getListByWhere([
            ['uid', 'IN', $uidArr],
            ['state', '=', 1],
            ['deleted', '=', 0]
        ], implode(',', $fieldArr));

        if (empty($data)) {
            return array();
        }

        return array_column($data, null, 'uid');
    }

    /**
     * 判断用户是否为主播或者公会长
     * @param array $uids
     * @param bool $isDiff
     * @param bool $isBroker
     * @return array
     */
    public static function checkUidBroker(array $uids, bool $isDiff = false, bool $isBroker = false): array
    {
        $absent = [];
        foreach (array_chunk($uids, 200) as $item) {
            $list = self::getBrokerUserBatch($item, ['uid']);
            if (empty($list)) {
                $isDiff && $absent = array_merge($absent, $item);
                continue;
            }

            $uids = array_column($list, 'uid');
            // 判断是否为主播，存在两种情况通过isDiff判断
            // 如果isDiff为true，则表示判断uid是否为主播，不存在则返回uid
            // 如果isDiff为false，则表示判断uid是否为主播，存在则返回uid
            if ($isDiff) {
                $diff = array_diff($item, $uids);
                $diff && $absent = array_merge($absent, $diff);
            } else {
                $intersect = array_intersect($item, $uids);
                $intersect && $absent = array_merge($absent, $intersect);
            }

            // 判断uid中是否存在公会长
            if ($isBroker) {
                $creaters = XsBroker::getListByCreater($item);
                $brokerIntersect = array_intersect($item, $creaters);
                $brokerIntersect && $absent = array_merge($absent, $brokerIntersect);
            }
        }

        return $absent;
    }

    /**
     * 获取公会下所有主播
     * @param int $bid
     * @return array
     */
    public static function getBrokerUsers(int $bid): array
    {
        $uids = [];

        $generator = self::getGeneratorListByWhere([
            ['bid', '=', $bid],
            ['state', '=', 1],
            ['deleted', '=', 0]
        ], 'id, bid, uid');

        foreach ($generator as $result) {
            foreach ($result as $item) {
                $uids[] = $item['uid'];
            }
        }

        return $uids;
    }

    /**
     * 根据用户id获取加入的公会id
     * @param int $uid
     * @return string
     */
    public static function getBrokerNameByUid($uid)
    {
        $model = self::findFirst([
            'conditions' => 'uid = :uid: and state = 1 and deleted = 0',
            'bind' => array(
                'uid' => intval($uid),
            ),
        ]);
        if (!$model) {
            return '';
        }

        $broker = XsBroker::getByBid($model->bid);
        return $broker ? $broker->bname : '';
    }

    /**
     * 用户是否加入了公会，成为gs
     * @param int $uid
     * @return mixed
     */
    public static function isGs($uid)
    {
        return self::findFirst(['uid = :uid: and state = 1 and deleted = 0', 'bind' => ['uid' => $uid]]);
    }

    /**
     * 根据bid批量获取公会信息
     * @param array $uidArr uid
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getBrokerUserBatchChounk($uidArr = [], $fieldArr = ['uid', 'bid'])
    {
        if (empty($uidArr)) {
            return [];
        }
        if (!in_array('uid', $fieldArr)) {
            $fieldArr[] = 'uid';
        }
        $uidChunk = array_chunk($uidArr,200);
        $dataMap = [];
        foreach ($uidChunk as $uids){
            $data = self::find(array(
                'columns' => implode(',', $fieldArr),
                'conditions' => "uid in ({uid:array}) and state = :state: and deleted = :deleted:",
                'bind' => array(
                    'uid' => $uids,
                    'state' => 1,
                    'deleted' => 0
                ),
            ))->toArray();
            if($data){
                foreach ($data as $v){
                    $dataMap[$v['uid']] = $v;
                }
            }
        }
        return $dataMap;
    }
}
